<?php

namespace TeamGantt\Dues\Processor;

use DateTime;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Transaction;

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

    /**
     * @param Subscription[] $subscriptions
     *
     * @return Subscription[]
     */
    public function cancelSubscriptions(array $subscriptions): array
    {
        return $this->gateway->cancelSubscriptions($subscriptions);
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

    /**
     * @param Status[] $statuses
     *
     * @return Subscription[]
     */
    public function findSubscriptionsByCustomerId(string $customerId, array $statuses = []): array
    {
        return $this->gateway->findSubscriptionsByCustomerId($customerId, $statuses);
    }

    public function findSubscriptionById(string $subscriptionId): ?Subscription
    {
        return $this->gateway->findSubscriptionById($subscriptionId);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?DateTime $start = null, ?DateTime $end = null): array
    {
        return $this->gateway->findTransactionsByCustomerId($customerId, $start, $end);
    }

    public function listAddOns(): array
    {
        return $this->gateway->listAddOns();
    }

    public function findAddOnById(string $id): ?AddOn
    {
        return $this->gateway->findAddOnById($id);
    }

    public function listDiscounts(): array
    {
        return $this->gateway->listDiscounts();
    }

    public function findDiscountById(string $id): ?Discount
    {
        return $this->gateway->findDiscountById($id);
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
