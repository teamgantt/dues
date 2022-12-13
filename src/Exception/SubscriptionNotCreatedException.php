<?php

namespace TeamGantt\Dues\Exception;

use TeamGantt\Dues\Model\Customer;

class SubscriptionNotCreatedException extends \RuntimeException
{
    protected ?Customer $customer = null;

    /**
     * @param mixed $message
     *
     * @return void
     */
    public function __construct($message, ?Customer $customer = null)
    {
        parent::__construct($message);
        $this->customer = $customer;
    }

    /**
     * Return a Customer model associated with the failed creation. This
     * represents a Customer created WITH the Subscription. Null implies
     * no Customer was created for the Subscription creation. This Customer
     * object may be used to rollback a user in the case of a failure related
     * to creating a subscription.
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
}
