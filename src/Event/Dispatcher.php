<?php

namespace TeamGantt\Dues\Event;

use SplObjectStorage;
use TeamGantt\Dues\Contracts\EventListener;
use TeamGantt\Dues\Contracts\EventListenerContainer;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Subscription;

class Dispatcher implements EventListenerContainer
{
    /**
     * @var SplObjectStorage<EventListener, EventListener>
     */
    protected SplObjectStorage $listeners;

    public function __construct()
    {
        $this->listeners = new SplObjectStorage();
    }

    public function addListener(EventListener $listener): void
    {
        $this->listeners->attach($listener);
    }

    public function removeListener(EventListener $listener): void
    {
        $this->listeners->detach($listener);
    }

    /**
     * @param Subscription|Customer $model
     */
    public function dispatch(EventType $type, $model): void
    {
        foreach ($this->listeners as $listener) {
            if ($model instanceof Customer) {
                if ($type->equals(EventType::afterCreateCustomer())) {
                    $listener->onAfterCreateCustomer($model);
                } elseif ($type->equals(EventType::afterUpdateCustomer())) {
                    $listener->onAfterUpdateCustomer($model);
                } elseif ($type->equals(EventType::beforeCreateCustomer())) {
                    $listener->onBeforeCreateCustomer($model);
                } elseif ($type->equals(EventType::beforeUpdateCustomer())) {
                    $listener->onBeforeUpdateCustomer($model);
                }
            }

            if ($model instanceof Subscription) {
                if ($type->equals(EventType::afterCreateSubscription())) {
                    $listener->onAfterCreateSubscription($model);
                } elseif ($type->equals(EventType::afterUpdateSubscription())) {
                    $listener->onAfterUpdateSubscription($model);
                } elseif ($type->equals(EventType::beforeCreateSubscription())) {
                    $listener->onBeforeCreateSubscription($model);
                } elseif ($type->equals(EventType::beforeUpdateSubscription())) {
                    $listener->onBeforeUpdateSubscription($model);
                }
            }
        }
    }
}
