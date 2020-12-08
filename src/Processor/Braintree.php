<?php

namespace TeamGantt\Dues\Processor;

use Braintree\Customer as BraintreeCustomer;
use Braintree\Error\Codes;
use Braintree\Gateway as BraintreeGateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Exception;
use RuntimeException;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Exception\CustomerNotCreatedException;
use TeamGantt\Dues\Exception\CustomerNotDeletedException;
use TeamGantt\Dues\Exception\CustomerNotUpdatedException;
use TeamGantt\Dues\Exception\InvariantException;
use TeamGantt\Dues\Exception\PaymentMethodNotCreatedException;
use TeamGantt\Dues\Exception\SubscriptionNotCanceledException;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Processor\Braintree\AddOnMapper;
use TeamGantt\Dues\Processor\Braintree\CustomerMapper;
use TeamGantt\Dues\Processor\Braintree\DiscountMapper;
use TeamGantt\Dues\Processor\Braintree\PaymentMethodMapper;
use TeamGantt\Dues\Processor\Braintree\PlanMapper;
use TeamGantt\Dues\Processor\Braintree\SubscriptionMapper;

class Braintree implements SubscriptionGateway
{
    private BraintreeGateway $braintree;

    private CustomerMapper $customerMapper;

    private PaymentMethodMapper $paymentMethodMapper;

    private SubscriptionMapper $subscriptionMapper;

    private AddOnMapper $addOnMapper;

    private DiscountMapper $discountMapper;

    private PlanMapper $planMapper;

    /**
     * @param array<mixed> $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->braintree = new BraintreeGateway($config);
        $this->paymentMethodMapper = new PaymentMethodMapper();
        $this->customerMapper = new CustomerMapper($this->paymentMethodMapper);
        $this->addOnMapper = new AddOnMapper();
        $this->discountMapper = new DiscountMapper();
        $this->subscriptionMapper = new SubscriptionMapper($this->addOnMapper, $this->discountMapper);
        $this->planMapper = new PlanMapper($this->addOnMapper, $this->discountMapper);
    }

    public function createCustomer(Customer $customer): Customer
    {
        $request = $this->customerMapper->toRequest($customer);

        $result = $this
            ->braintree
            ->customer()
            ->create($request);

        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'Unknown message';
            throw new CustomerNotCreatedException($message);
        }

        if (!isset($result->customer)) {
            throw new InvariantException('Result has no customer property');
        }

        $newCustomer = $this->customerMapper->fromResult($result->customer);

        $newMethods = array_reduce(
            $customer->getPaymentMethods(),
            fn (array $r, PaymentMethod $m) => [...$r, $this->createPaymentMethod($m->setCustomer($newCustomer))],
            []
        );

        return $newCustomer->setPaymentMethods($newMethods);
    }

    public function updateCustomer(Customer $customer): Customer
    {
        if ($customer->isNew()) {
            throw new CustomerNotUpdatedException('Cannot update a new customer');
        }

        // Create any new payment methods
        $allPaymentMethods = $customer->getPaymentMethods();
        $paymentMethods = [];
        foreach ($allPaymentMethods as $paymentMethod) {
            if ($paymentMethod->isNew()) {
                $paymentMethods[] = ($this->createPaymentMethod($paymentMethod))->setIsDefaultPaymentMethod($paymentMethod->isDefaultPaymentMethod());
            } else {
                $paymentMethods[] = $paymentMethod;
            }
        }
        $customer->setPaymentMethods($paymentMethods);

        $id = $customer->getId();
        if (null === $id) {
            throw new UnknownException('Could not find customer ID');
        }

        // Update the user @todo rollback payment methods
        $request = $this->customerMapper->toRequest($customer);
        $result = $this
            ->braintree
            ->customer()
            ->update($id, $request);

        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'Unknown message';
            throw new CustomerNotUpdatedException($message);
        }

        $customer = $this->findCustomerById($id);
        if (null === $customer) {
            throw new UnknownException('Could not find updated customer');
        }

        return $customer;
    }

    public function deleteCustomer(string $customerId): void
    {
        try {
            $result = $this->braintree->customer()->delete($customerId);
            if (!$result->success) {
                $message = isset($result->message) ? $result->message : 'Unknown message';
                throw new CustomerNotDeletedException($message);
            }
        } catch (Exception $e) {
            throw new CustomerNotDeletedException($e->getMessage());
        }
    }

    public function findCustomerById(string $customerId): ?Customer
    {
        if ($customerResult = $this->findBraintreeCustomerById($customerId)) {
            return $this->customerMapper->fromResult($customerResult);
        }

        return null;
    }

    /**
     * @param Status[] $statuses
     *
     * @return Subscription[]
     */
    public function findSubscriptionsByCustomerId(string $customerId, array $statuses = []): array
    {
        $customerResult = $this->findBraintreeCustomerById($customerId);

        if (null === $customerResult) {
            return [];
        }

        $subscriptions = Arr::mapcat($customerResult->paymentMethods, function ($pm) {
            return array_map([$this->subscriptionMapper, 'fromResult'], $pm->subscriptions);
        });

        if (empty($statuses)) {
            return $subscriptions;
        }

        return array_values(array_filter($subscriptions, fn (Subscription $sub) => in_array($sub->getStatus(), $statuses)));
    }

    private function findBraintreeCustomerById(string $customerId): ?BraintreeCustomer
    {
        try {
            $result = $this
                ->braintree
                ->customer()
                ->find($customerId);

            if (is_bool($result)) {
                throw new UnknownException('Customer find request failed');
            }

            return $result;
        } catch (UnknownException $e) {
            throw $e;
        } catch (Exception $e) {
            return null;
        }
    }

    public function findSubscriptionById(string $subscriptionId): ?Subscription
    {
        try {
            $result = $this
                ->braintree
                ->subscription()
                ->find($subscriptionId);

            return $this->subscriptionMapper->fromResult($result);
        } catch (Exception $e) {
            return null;
        }
    }

    public function cancelSubscription(string $subscriptionId): Subscription
    {
        try {
            $this
                ->braintree
                ->subscription()
                ->cancel($subscriptionId);

            if ($result = $this->findSubscriptionById($subscriptionId)) {
                return $result;
            }

            throw new RuntimeException('Subscription not found');
        } catch (Exception $e) {
            throw new SubscriptionNotCanceledException($e->getMessage());
        }
    }

    /**
     * @param Subscription[] $subscriptions
     *
     * @return Subscription[]
     */
    public function cancelSubscriptions(array $subscriptions): array
    {
        $canceled = [];
        foreach ($subscriptions as $subscription) {
            if ($subscription->isNot(Status::canceled(), Status::expired())) {
                $canceled[] = $this->cancelSubscription($subscription->getId() ?? '');
            }
        }

        return $canceled;
    }

    public function createPaymentMethod(PaymentMethod $paymentMethod): Token
    {
        $request = $this->paymentMethodMapper->toRequest($paymentMethod);

        $result = $this
            ->braintree
            ->paymentMethod()
            ->create($request);

        if (!$result->success) {
            throw new PaymentMethodNotCreatedException($result->message);
        }

        return $this->paymentMethodMapper->fromResult($result->paymentMethod);
    }

    public function createSubscription(Subscription $subscription): Subscription
    {
        $customer = $subscription->getCustomer();
        $isNewCustomer = false;

        $plan = $subscription->getPlan();

        if (null === $plan) {
            throw new SubscriptionNotCreatedException('Cannot create Subscription without a Plan');
        }

        $plan = $this->findPlanById($plan->getId() ?? '');
        $subscription->setPlan($plan);

        if ($customer && $customer->isNew()) {
            $isNewCustomer = true;
            $subscription->setCustomer($this->createCustomer($customer));
        }

        $request = $this->subscriptionMapper->toRequest($subscription);
        $request = Arr::dissoc($request, ['id', 'status']);

        $result = $this
            ->braintree
            ->subscription()
            ->create($request);

        if ($result->success) {
            $newSubscription = $this->subscriptionMapper->fromResult($result->subscription);

            return $newSubscription->setCustomer($subscription->getCustomer());
        }

        if (!$isNewCustomer) {
            throw new SubscriptionNotCreatedException($result->message);
        }

        throw new SubscriptionNotCreatedException($result->message, $subscription->getCustomer());
    }

    public function updateSubscription(Subscription $subscription): Subscription
    {
        try {
            $result = $this->doBraintreeUpdate($subscription);

            if (!$result->success && $result instanceof Error) {
                $isBillingCycleError = !empty(array_filter(
                    $result->errors->deepAll(),
                    fn ($error) => Codes::SUBSCRIPTION_PLAN_BILLING_FREQUENCY_CANNOT_BE_UPDATED === $error->code
                ));

                if (!$isBillingCycleError) {
                    $message = isset($result->message) ? $result->message : 'An unknown update error occurred';
                    throw new SubscriptionNotUpdatedException($message);
                }

                return $this->changeSubscriptionBillingCycle($subscription);
            }

            $updated = $this->findSubscriptionById($subscription->getId() ?? '');

            if (null == $updated) {
                throw new UnknownException('Failed to find updated Subscription');
            }

            return $subscription->merge($updated);
        } catch (Exception $e) {
            throw new SubscriptionNotUpdatedException($e->getMessage());
        }
    }

    private function changeSubscriptionBillingCycle(Subscription $subscription): Subscription
    {
        $customer = $subscription->getCustomer();
        $newPlan = $subscription->getPlan();
        $newPrice = $subscription->getPrice();
        $newAddOns = $subscription->getAddOns();
        $newDiscounts = $subscription->getDiscounts();

        if (!isset($newPlan, $customer)) {
            throw new SubscriptionNotUpdatedException('Cannot change subscription billing cycle with null plan or customer');
        }

        // Zero out subscription to get a balance
        $subscription->closeOut();
        $result = $this->doBraintreeUpdate($subscription);
        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'An unknown update error occurred';
            throw new SubscriptionNotUpdatedException($message);
        }
        $updated = $this->findSubscriptionById((string) $subscription->getId());

        if (null === $updated) {
            throw new UnknownException('Failed to fetch updated subscription');
        }

        // Cancel subscription
        $canceled = $this->cancelSubscription((string) $updated->getId())
            ->closeOut()
            ->setPrice(null);

        // Purchase new subscription, applying balance from previous subscription.
        $builder = (new SubscriptionBuilder())
            ->withPlan($newPlan)
            ->withCustomer($customer)
            ->withDiscounts($newDiscounts->getAll())
            ->withAddOns($newAddOns->getAll());

        if (!empty($newPrice)) {
            $builder->withPrice($newPrice);
        }

        $newSubscription = $builder->build();

        return $this->createSubscription($newSubscription->merge($canceled));
    }

    public function listPlans(): array
    {
        $results = $this
            ->braintree
            ->plan()
            ->all();

        return array_reduce($results, function ($r, $i) {
            $plan = $this->planMapper->fromResult($i);

            return [...$r, $plan];
        }, []);
    }

    public function listAddOns(): array
    {
        $results = $this
            ->braintree
            ->addOn()
            ->all();

        return $this->addOnMapper->fromResults($results);
    }

    public function listDiscounts(): array
    {
        $results = $this
            ->braintree
            ->discount()
            ->all();

        return $this->discountMapper->fromResults($results);
    }

    public function findPlanById(string $planId): ?Plan
    {
        $plans = $this->listPlans();

        foreach ($plans as $plan) {
            if ($plan->getId() === $planId) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @return Successful|Error
     */
    private function doBraintreeUpdate(Subscription $subscription)
    {
        $request = $this->subscriptionMapper->toRequest($subscription);
        $request = Arr::dissoc($request, ['firstBillingDate', 'status']);
        $request['options'] = ['prorateCharges' => true];

        return $this
            ->braintree
            ->subscription()
            ->update($request['id'], $request);
    }
}
