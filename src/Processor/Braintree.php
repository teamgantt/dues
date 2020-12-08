<?php

namespace TeamGantt\Dues\Processor;

use Braintree\Error\Codes;
use Braintree\Gateway as BraintreeGateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Exception;
use RuntimeException;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Exception\SubscriptionNotCanceledException;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Processor\Braintree\Mapper\AddOnMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\CustomerMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\DiscountMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PaymentMethodMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PlanMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Repository\CustomerRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PaymentMethodRepository;

class Braintree implements SubscriptionGateway
{
    private BraintreeGateway $braintree;

    private PaymentMethodRepository $paymentMethods;

    private CustomerRepository $customers;

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

        $paymentMethodMapper = new PaymentMethodMapper();
        $customerMapper = new CustomerMapper($paymentMethodMapper);

        $this->paymentMethods = new PaymentMethodRepository($this->braintree, $paymentMethodMapper);
        $this->customers = new CustomerRepository($this->braintree, $customerMapper, $this->paymentMethods);

        $this->addOnMapper = new AddOnMapper();
        $this->discountMapper = new DiscountMapper();
        $this->subscriptionMapper = new SubscriptionMapper($this->addOnMapper, $this->discountMapper);
        $this->planMapper = new PlanMapper($this->addOnMapper, $this->discountMapper);
    }

    public function createCustomer(Customer $customer): Customer
    {
        return $this->customers->add($customer);
    }

    public function updateCustomer(Customer $customer): Customer
    {
        return $this->customers->update($customer);
    }

    public function deleteCustomer(string $customerId): void
    {
        $this->customers->remove($customerId);
    }

    public function findCustomerById(string $customerId): ?Customer
    {
        return $this->customers->find($customerId);
    }

    /**
     * @param Status[] $statuses
     *
     * @return Subscription[]
     */
    public function findSubscriptionsByCustomerId(string $customerId, array $statuses = []): array
    {
        $customerResult = $this->customers->findBraintreeCustomer($customerId);

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
                $canceled[] = $this->cancelSubscription($subscription->getId());
            }
        }

        return $canceled;
    }

    public function createPaymentMethod(PaymentMethod $paymentMethod): Token
    {
        return $this->paymentMethods->add($paymentMethod);
    }

    public function createSubscription(Subscription $subscription): Subscription
    {
        $customer = $subscription->getCustomer();
        $isNewCustomer = false;

        $plan = $subscription->getPlan();
        $plan = $this->findPlanById($plan->getId());

        if (null === $plan) {
            throw new SubscriptionNotCreatedException('Failed to fetch subscription plan');
        }

        $subscription->setPlan($plan);

        if ($customer->isNew()) {
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

            return $newSubscription
                ->setCustomer($subscription->getCustomer())
                ->resetPlan($plan);
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

            $updated = $this->findSubscriptionById($subscription->getId());

            if (null == $updated) {
                throw new UnknownException('Failed to find updated Subscription');
            }

            $newPlan = $this->findPlanById($updated->getPlan()->getId());

            if (null !== $newPlan) {
                $updated->setPlan($newPlan);
            }

            return $subscription->merge($updated);
        } catch (Exception $e) {
            throw new SubscriptionNotUpdatedException($e->getMessage());
        }
    }

    private function changeSubscriptionBillingCycle(Subscription $subscription): Subscription
    {
        $newPlan = $subscription->getPlan();
        $newPrice = $subscription->getPrice();
        $newAddOns = $subscription->getAddOns();
        $newDiscounts = $subscription->getDiscounts();

        // Zero out subscription to get a balance
        $subscription->closeOut();
        $result = $this->doBraintreeUpdate($subscription);
        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'An unknown update error occurred';
            throw new SubscriptionNotUpdatedException($message);
        }
        $updated = $this->findSubscriptionById($subscription->getId());

        if (null === $updated) {
            throw new UnknownException('Failed to fetch updated subscription');
        }

        // Cancel subscription
        $canceled = $this->cancelSubscription($updated->getId())
            ->closeOut()
            ->setPrice(new NullPrice());

        // Purchase new subscription, applying balance from previous subscription.
        $builder = (new SubscriptionBuilder())
            ->withPlan($newPlan)
            ->withCustomer($subscription->getCustomer())
            ->withDiscounts($newDiscounts->getAll())
            ->withAddOns($newAddOns->getAll());

        if ($balance = $canceled->getBalance()) {
            $balanceDiscount = new Discount('balance', 1, new Price(abs($balance->getAmount())));

            $builder->withDiscount($balanceDiscount);
        }

        if (!empty($newPrice)) {
            $builder->withPrice($newPrice);
        }

        $newSubscription = $builder->build();
        $discounts = $newSubscription->getDiscounts();
        $addOns = $newSubscription->getAddOns();
        $newSubscription = $newSubscription
            ->merge($canceled)
            ->setCustomer($subscription->getCustomer())
            ->setAddOns($addOns)
            ->setDiscounts($discounts);

        $subscription->merge($canceled);

        return $this->createSubscription($newSubscription);
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
