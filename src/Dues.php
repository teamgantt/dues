<?php

namespace TeamGantt\Dues;

use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Exception\CustomerNotUpdatedException;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Processor\ProcessesSubscriptions;

/**
 * Dues does subscriptions dawgz.
 */
class Dues implements SubscriptionGateway
{
    /*
     * Dues processes subscriptions. Includes the ProcessesSubscriptions trait
     * in order to provide the basic gateway API, but allows this class to focus
     * on the things that make Dues special and unique
     */
    use ProcessesSubscriptions {
        createSubscription as traitCreateSubscription;
    }

    private SubscriptionGateway $gateway;

    public function __construct(SubscriptionGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Create a new subscription. Supports creating a new customer in tandem with
     * the new subscription. If subscription creation fails when a new user is present,
     * the user will be rolled back to prevent orphaned customers.
     */
    public function createSubscription(Subscription $subscription): Subscription
    {
        try {
            return $this->traitCreateSubscription($subscription);
        } catch (SubscriptionNotCreatedException $e) { //rollback any new customers
            if ($customer = $e->getCustomer()) {
                $this->deleteCustomer($customer->getId() ?? '');
            }
            throw $e;
        }
    }

    /**
     * Change a customer's payment method. Supports new and existing payment methods.
     * The given payment method will be set to the default payment method, and all
     * active subscriptions will be updated to use the new payment method.
     */
    public function changePaymentMethod(Customer $customer, PaymentMethod $paymentMethod): Customer
    {
        // Attach the payment method and set it as the default payment method
        $paymentMethod->setIsDefaultPaymentMethod(true);
        $customer->addPaymentMethod($paymentMethod);
        $customer = $this->updateCustomer($customer);

        // Update all subscriptions with the new payment method
        $paymentMethod = $customer->getDefaultPaymentMethod();

        if (null === $paymentMethod) {
            throw new CustomerNotUpdatedException('Failed to set default payment method for Customer');
        }

        $subscriptions = $this->findSubscriptionsByCustomerId((string) $customer->getId());
        foreach ($subscriptions as $subscription) {
            if ($subscription->isNot(Status::active(), Status::pending(), Status::pastDue())) {
                continue;
            }
            $subscription->setPaymentMethod($paymentMethod);
            $this->updateSubscription($subscription);
        }

        return $customer;
    }
}
