<?php

namespace TeamGantt\Dues\Contracts;

use DateTime;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Transaction;

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

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?DateTime $start = null, ?DateTime $end = null): array;

    /**
     * @return AddOn[]
     */
    public function listAddOns(): array;

    /**
     * @return Discount[]
     */
    public function listDiscounts(): array;

    /**
     * @return Plan[]
     */
    public function listPlans(): array;

    public function findPlanById(string $planId): ?Plan;
}
