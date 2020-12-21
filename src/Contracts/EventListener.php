<?php

namespace TeamGantt\Dues\Contracts;

use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Subscription;

interface EventListener
{
    public function onAfterCreateCustomer(Customer $customer): void;

    public function onAfterUpdateCustomer(Customer $customer): void;

    public function onBeforeCreateCustomer(Customer $customer): void;

    public function onBeforeUpdateCustomer(Customer $customer): void;

    public function onAfterCreateSubscription(Subscription $subscription): void;

    public function onAfterUpdateSubscription(Subscription $subscription): void;

    public function onBeforeCreateSubscription(Subscription $subscription): void;

    public function onBeforeUpdateSubscription(Subscription $subscription): void;
}
