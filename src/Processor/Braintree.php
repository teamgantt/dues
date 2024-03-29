<?php

namespace TeamGantt\Dues\Processor;

use Braintree\Gateway as BraintreeGateway;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Event\Dispatcher;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Customer\CustomerSession;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Processor\Braintree\Hydrator\SubscriptionHydrator;
use TeamGantt\Dues\Processor\Braintree\Mapper\AddOnMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\CustomerMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\DiscountMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PaymentMethodMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\PlanMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\StatusHistoryMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\TransactionMapper;
use TeamGantt\Dues\Processor\Braintree\Query\SubscriptionQuery;
use TeamGantt\Dues\Processor\Braintree\Repository\AddOnRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\CustomerRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\DiscountRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PaymentMethodRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\SubscriptionRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\TransactionRepository;

class Braintree implements SubscriptionGateway
{
    private BraintreeGateway $braintree;

    private SubscriptionMapper $subscriptionMapper;

    private PaymentMethodRepository $paymentMethods;

    private CustomerRepository $customers;

    private PlanRepository $plans;

    private AddOnRepository $addOns;

    private DiscountRepository $discounts;

    private SubscriptionRepository $subscriptions;

    private TransactionRepository $transactions;

    private ?Dispatcher $events;

    private SubscriptionHydrator $hydrator;

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
        $statusHistoryMapper = new StatusHistoryMapper();
        $subscriptionMapper = new SubscriptionMapper($addOnMapper, $discountMapper, $transactionMapper, $statusHistoryMapper);
        $paymentMethodMapper = new PaymentMethodMapper();
        $customerMapper = new CustomerMapper($paymentMethodMapper);
        $planMapper = new PlanMapper($addOnMapper, $discountMapper);

        $this->braintree = $braintree;
        $this->subscriptionMapper = $subscriptionMapper;

        $this->paymentMethods = new PaymentMethodRepository($braintree, $paymentMethodMapper);
        $this->customers = new CustomerRepository($braintree, $customerMapper, $this->paymentMethods);
        $this->plans = new PlanRepository($braintree, $planMapper);
        $this->hydrator = new SubscriptionHydrator($this->customers, $this->plans, $this->paymentMethods);
        $this->discounts = new DiscountRepository($braintree, $discountMapper);
        $this->addOns = new AddOnRepository($braintree, $addOnMapper);
        $this->subscriptions = new SubscriptionRepository($braintree, $subscriptionMapper, $this->customers, $this->plans, $this->hydrator);
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

    public function findTransactionById(string $transactionId): ?Transaction
    {
        return $this->transactions->find($transactionId);
    }

    public function createCustomerSession(?string $customerId = null): CustomerSession
    {
        return $this->customers->createCustomerSession($customerId);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?\DateTime $start = null, ?\DateTime $end = null): array
    {
        return $this->transactions->findByCustomerId($customerId, $start, $end);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsBySubscriptionId(string $subscriptionId): array
    {
        $subscription = $this->findSubscriptionById($subscriptionId);

        if (null === $subscription) {
            return [];
        }

        return $subscription->getTransactions();
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

    public function findAddOnById(string $id): ?AddOn
    {
        return $this->addOns->find($id);
    }

    public function listDiscounts(): array
    {
        return $this->discounts->all();
    }

    public function findDiscountById(string $id): ?Discount
    {
        return $this->discounts->find($id);
    }

    public function findPlanById(string $planId): ?Plan
    {
        return $this->plans->find($planId);
    }

    public function makeSubscriptionQuery(): SubscriptionQuery
    {
        return new SubscriptionQuery($this->braintree, $this->subscriptionMapper, $this->hydrator);
    }

    public function setDispatcher(Dispatcher $events): void
    {
        $this->events = $events;
        $this->subscriptions->setDispatcher($this->events);
    }
}
