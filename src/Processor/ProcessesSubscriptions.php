<?php

namespace TeamGantt\Dues\Processor;

use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;

/**
 * This trait exists to provide simple delegation to a SubscriptionGateway.
 */
trait ProcessesSubscriptions
{
    private SubscriptionGateway $gateway;

    public function createCustomer(Customer $customer): Customer
    {
        return $this->gateway->createCustomer($customer);
    }

    public function updateCustomer(Customer $customer): Customer
    {
        return $this->gateway->updateCustomer($customer);
    }

    public function deleteCustomer(string $customerId): void
    {
        $this->gateway->deleteCustomer($customerId);
    }

    public function cancelSubscription(string $subscriptionId): Subscription
    {
        return $this->gateway->cancelSubscription($subscriptionId);
    }

    public function createPaymentMethod(PaymentMethod $paymentMethod): PaymentMethod
    {
        return $this->gateway->createPaymentMethod($paymentMethod);
    }

    public function createSubscription(Subscription $subscription): Subscription
    {
        return $this->gateway->createSubscription($subscription);
    }

    public function updateSubscription(Subscription $subscription): Subscription
    {
        return $this->gateway->updateSubscription($subscription);
    }

    public function findCustomerById(string $customerId): ?Customer
    {
        return $this->gateway->findCustomerById($customerId);
    }

    public function findSubscriptionsByCustomerId(string $customerId): array
    {
        return $this->gateway->findSubscriptionsByCustomerId($customerId);
    }

    public function findSubscriptionById(string $subscriptionId): ?Subscription
    {
        return $this->gateway->findSubscriptionById($subscriptionId);
    }

    public function listAddOns(): array
    {
        return $this->gateway->listAddOns();
    }

    public function listDiscounts(): array
    {
        return $this->gateway->listDiscounts();
    }

    public function listPlans(): array
    {
        return $this->gateway->listPlans();
    }

    public function findPlanById(string $planId): ?Plan
    {
        return $this->gateway->findPlanById($planId);
    }
}
