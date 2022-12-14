<?php

namespace TeamGantt\Dues\Processor;

use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Event\Dispatcher;
use TeamGantt\Dues\Event\EventType;
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

/**
 * This trait exists to provide simple delegation to a SubscriptionGateway.
 */
trait ProcessesSubscriptions
{
    private SubscriptionGateway $gateway;

    private Dispatcher $events;

    public function createCustomer(Customer $customer): Customer
    {
        $this->events->dispatch(EventType::beforeCreateCustomer(), $customer);
        $result = $this->gateway->createCustomer($customer);
        $this->events->dispatch(EventType::afterCreateCustomer(), $result);

        return $result;
    }

    public function updateCustomer(Customer $customer): Customer
    {
        $this->events->dispatch(EventType::beforeUpdateCustomer(), $customer);
        $result = $this->gateway->updateCustomer($customer);
        $this->events->dispatch(EventType::afterUpdateCustomer(), $result);

        return $result;
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
        $result = $this->gateway->createSubscription($subscription);
        $this->events->dispatch(EventType::afterCreateSubscription(), $result);

        return $result;
    }

    public function updateSubscription(Subscription $subscription): Subscription
    {
        $this->events->dispatch(EventType::beforeUpdateSubscription(), $subscription);
        $result = $this->gateway->updateSubscription($subscription);
        $this->events->dispatch(EventType::afterUpdateSubscription(), $result);

        return $result;
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

    public function createCustomerSession(?string $customerId = null): CustomerSession
    {
        return $this->gateway->createCustomerSession($customerId);
    }

    public function findTransactionById(string $transactionId): ?Transaction
    {
        return $this->gateway->findTransactionById($transactionId);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByCustomerId(string $customerId, ?\DateTime $start = null, ?\DateTime $end = null): array
    {
        return $this->gateway->findTransactionsByCustomerId($customerId, $start, $end);
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsBySubscriptionId(string $subscriptionId): array
    {
        return $this->gateway->findTransactionsBySubscriptionId($subscriptionId);
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

    public function makeSubscriptionQuery(): SubscriptionQuery
    {
        return $this->gateway->makeSubscriptionQuery();
    }
}
