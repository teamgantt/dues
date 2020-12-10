<?php

namespace TeamGantt\Dues\Processor;

use Braintree\Gateway as BraintreeGateway;
use DateTime;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Processor\Braintree\Mapper\AddOnMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\CustomerMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\DiscountMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PaymentMethodMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PlanMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\TransactionMapper;
use TeamGantt\Dues\Processor\Braintree\Repository\AddOnRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\CustomerRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\DiscountRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PaymentMethodRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\SubscriptionRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\TransactionRepository;

class Braintree implements SubscriptionGateway
{
    private PaymentMethodRepository $paymentMethods;

    private CustomerRepository $customers;

    private PlanRepository $plans;

    private AddOnRepository $addOns;

    private DiscountRepository $discounts;

    private SubscriptionRepository $subscriptions;

    private TransactionRepository $transactions;

    /**
     * @param array<mixed> $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $braintree = new BraintreeGateway($config);

        $addOnMapper = new AddOnMapper();
        $discountMapper = new DiscountMapper();
        $transactionMapper = new TransactionMapper($addOnMapper, $discountMapper);
        $subscriptionMapper = new SubscriptionMapper($addOnMapper, $discountMapper, $transactionMapper);
        $paymentMethodMapper = new PaymentMethodMapper();
        $customerMapper = new CustomerMapper($paymentMethodMapper);
        $planMapper = new PlanMapper($addOnMapper, $discountMapper);

        $this->paymentMethods = new PaymentMethodRepository($braintree, $paymentMethodMapper);
        $this->customers = new CustomerRepository($braintree, $customerMapper, $this->paymentMethods);
        $this->plans = new PlanRepository($braintree, $planMapper);
        $this->discounts = new DiscountRepository($braintree, $discountMapper);
        $this->addOns = new AddOnRepository($braintree, $addOnMapper);
        $this->subscriptions = new SubscriptionRepository($braintree, $subscriptionMapper, $this->customers, $this->plans);
        $this->transactions = new TransactionRepository($braintree, $transactionMapper);
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
        return $this->subscriptions->findByCustomerId($customerId, $statuses);
    }

    public function findSubscriptionById(string $subscriptionId): ?Subscription
    {
        return $this->subscriptions->find($subscriptionId);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?DateTime $start = null, ?DateTime $end = null): array
    {
        return $this->transactions->findByCustomerId($customerId, $start, $end);
    }

    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $subscription = (new Subscription($subscriptionId))->setStatus(Status::canceled());

        return $this->subscriptions->update($subscription);
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
        return $this->subscriptions->add($subscription);
    }

    public function updateSubscription(Subscription $subscription): Subscription
    {
        return $this->subscriptions->update($subscription);
    }

    public function listPlans(): array
    {
        return $this->plans->all();
    }

    public function listAddOns(): array
    {
        return $this->addOns->all();
    }

    public function listDiscounts(): array
    {
        return $this->discounts->all();
    }

    public function findPlanById(string $planId): ?Plan
    {
        return $this->plans->find($planId);
    }
}
