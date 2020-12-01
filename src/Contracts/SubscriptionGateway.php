<?php

namespace TeamGantt\Dues\Contracts;

use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\AddOn;
use TeamGantt\Dues\Model\Subscription\Discount;

interface SubscriptionGateway
{
    public function createCustomer(Customer $customer): Customer;

    public function updateCustomer(Customer $customer): Customer;

    public function createPaymentMethod(PaymentMethod $paymentMethod): PaymentMethod;

    public function createSubscription(Subscription $subscription): Subscription;

    public function findCustomerById(string $customerId): ?Customer;

    public function deleteCustomer(string $customerId): void;

    public function cancelSubscription(string $subscriptionId): Subscription;

    public function updateSubscription(Subscription $subscription): Subscription;

    /**
     * @return Subscription[]
     */
    public function findSubscriptionsByCustomerId(string $customerId): array;

    public function findSubscriptionById(string $subscriptionId): ?Subscription;

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
