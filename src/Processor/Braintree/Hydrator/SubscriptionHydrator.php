<?php

namespace TeamGantt\Dues\Processor\Braintree\Hydrator;

use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod\Token;
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

                $customer = isset($cache[$token])
                    ? $cache[$token]
                    : $this->customers->findByPaymentToken($token);

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
        $cache = [];

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();

            $hydrated = isset($cache[$plan->getId()]) ? $cache[$plan->getId()] : $this->plans->find($plan->getId());

            if (null === $hydrated) {
                continue;
            }

            if (!isset($cache[$hydrated->getId()])) {
                $cache[$hydrated->getId()] = $hydrated;
            }

            $subscription->setPlan($hydrated);
        }
    }
}
