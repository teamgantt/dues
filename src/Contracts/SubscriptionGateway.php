<?php

namespace TeamGantt\Dues\Contracts;

use TeamGantt\Dues\Event\Dispatcher;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Customer\CustomerSession;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Processor\Braintree\Query\SubscriptionQuery;

interface SubscriptionGateway
{
    public function createCustomer(Customer $customer): Customer;

    public function updateCustomer(Customer $customer): Customer;

    public function createPaymentMethod(PaymentMethod $paymentMethod): PaymentMethod;

    public function createSubscription(Subscription $subscription): Subscription;

    public function findCustomerById(string $customerId): ?Customer;

    public function deleteCustomer(string $customerId): void;

    public function cancelSubscription(string $subscriptionId): Subscription;

    /**
     * @param Subscription[] $subscriptions
     *
     * @return Subscription[]
     */
    public function cancelSubscriptions(array $subscriptions): array;

    public function updateSubscription(Subscription $subscription): Subscription;

    /**
     * @param Status[] $statuses
     *
     * @return Subscription[]
     */
    public function findSubscriptionsByCustomerId(string $customerId, array $statuses = []): array;

    public function findSubscriptionById(string $subscriptionId): ?Subscription;

    public function findTransactionById(string $transactionId): ?Transaction;

    public function createCustomerSession(?string $customerId = null): CustomerSession;

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?\DateTime $start = null, ?\DateTime $end = null): array;

    /**
     * @return Transaction[]
     */
    public function findTransactionsBySubscriptionId(string $subscriptionId): array;

    /**
     * @return AddOn[]
     */
    public function listAddOns(): array;

    public function findAddOnById(string $id): ?AddOn;

    /**
     * @return Discount[]
     */
    public function listDiscounts(): array;

    public function findDiscountById(string $id): ?Discount;

    /**
     * @return Plan[]
     */
    public function listPlans(): array;

    public function findPlanById(string $planId): ?Plan;

    public function makeSubscriptionQuery(): SubscriptionQuery;

    /**
     * @internal this is so dues can pass event dispatch control to an individual gateway
     */
    public function setDispatcher(Dispatcher $events): void;
}
