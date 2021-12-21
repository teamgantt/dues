<?php

namespace TeamGantt\Dues\Processor\Braintree\Hydrator;

use Braintree\Exception\Unexpected;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Repository\CustomerRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PaymentMethodRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;

class SubscriptionHydrator
{
    protected CustomerRepository $customers;

    protected PlanRepository $plans;

    protected PaymentMethodRepository $paymentMethods;

    public function __construct(
        CustomerRepository $customers,
        PlanRepository $plans,
        PaymentMethodRepository $paymentMethods
    ) {
        $this->customers = $customers;
        $this->plans = $plans;
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * Hydrates subscriptions with Plans and Customer info.
     *
     * @param Subscription[] $subscriptions
     */
    public function hydrate(array $subscriptions): void
    {
        $this->hydratePlans($subscriptions);
        $this->hydrateCustomer($subscriptions);
    }

    /**
     * @param Subscription[] $subscriptions
     */
    private function hydrateCustomer(array $subscriptions): void
    {
        /**
         * @var Customer[]
         */
        $cache = [];

        foreach ($subscriptions as $subscription) {
            $customer = null;
            $paymentMethod = $subscription->getPaymentMethod();

            if ($paymentMethod instanceof Token) {
                $token = $paymentMethod->getValue();

                /**
                 * @var Customer|null
                 */
                $customer = isset($cache[$token])
                    ? $cache[$token]
                    : $this->braintreeRequestWithRetry(fn () => $this->customers->findByPaymentToken($token));

                $cache[$token] = $customer;
            }

            if (null === $customer) {
                continue;
            }

            $subscription->setCustomer($customer);
        }
    }

    /**
     * @param Subscription[] $subscriptions
     */
    private function hydratePlans(array $subscriptions): void
    {
        /**
         * @var Plan[]
         */
        $cache = [];

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();

            /**
             * @var Plan|null
             */
            $hydrated = isset($cache[$plan->getId()])
                ? $cache[$plan->getId()]
                : $this->braintreeRequestWithRetry(fn () => $this->plans->find($plan->getId()));

            if (null === $hydrated) {
                continue;
            }

            if (!isset($cache[$hydrated->getId()])) {
                $cache[$hydrated->getId()] = $hydrated;
            }

            $subscription->setPlan($hydrated);
        }
    }

    /**
     * Makes a request to Braintree and handles progressive back off in the case of network errors.
     *
     * @return mixed
     */
    private function braintreeRequestWithRetry(callable $fn, int $attempt = 0)
    {
        /**
         * Number of seconds to delay before retrying a request.
         */
        $backoff = [2, 4, 16];

        try {
            return call_user_func($fn);
        } catch (Unexpected $e) {
            // Max attempts has been reached, throw the error
            if ($attempt >= count($backoff)) {
                throw $e;
            }

            if (503 === $e->getCode()) {
                sleep($backoff[$attempt]);

                return $this->braintreeRequestWithRetry($fn, ++$attempt);
            }

            throw $e;
        }
    }
}
