<?php

namespace TeamGantt\Dues\Tests\Event;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Contracts\EventListener;
use TeamGantt\Dues\Event\Dispatcher;
use TeamGantt\Dues\Event\EventType;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Subscription;

final class DispatcherTest extends TestCase
{
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function testDispatchAfterCreateCustomer()
    {
        $listener = $this->createMock(EventListener::class);
        $customer = new Customer('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onAfterCreateCustomer')
            ->with($customer);

        $this->dispatcher->dispatch(EventType::afterCreateCustomer(), $customer);
    }

    public function testDispatchAfterUpdateCustomer()
    {
        $listener = $this->createMock(EventListener::class);
        $customer = new Customer('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onAfterUpdateCustomer')
            ->with($customer);

        $this->dispatcher->dispatch(EventType::afterUpdateCustomer(), $customer);
    }

    public function testDispatchBeforeCreateCustomer()
    {
        $listener = $this->createMock(EventListener::class);
        $customer = new Customer();
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onBeforeCreateCustomer')
            ->with($customer);

        $this->dispatcher->dispatch(EventType::beforeCreateCustomer(), $customer);
    }

    public function testDispatchBeforeUpdateCustomer()
    {
        $listener = $this->createMock(EventListener::class);
        $customer = new Customer('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onBeforeUpdateCustomer')
            ->with($customer);

        $this->dispatcher->dispatch(EventType::beforeUpdateCustomer(), $customer);
    }

    public function testDispatchAfterCreateSubscription()
    {
        $listener = $this->createMock(EventListener::class);
        $subscription = new Subscription('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onAfterCreateSubscription')
            ->with($subscription);

        $this->dispatcher->dispatch(EventType::afterCreateSubscription(), $subscription);
    }

    public function testDispatchAfterUpdateSubscription()
    {
        $listener = $this->createMock(EventListener::class);
        $subscription = new Subscription('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onAfterUpdateSubscription')
            ->with($subscription);

        $this->dispatcher->dispatch(EventType::afterUpdateSubscription(), $subscription);
    }

    public function testDispatchBeforeCreateSubscription()
    {
        $listener = $this->createMock(EventListener::class);
        $subscription = new Subscription();
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onBeforeCreateSubscription')
            ->with($subscription);

        $this->dispatcher->dispatch(EventType::beforeCreateSubscription(), $subscription);
    }

    public function testDispatchBeforeUpdateSubscription()
    {
        $listener = $this->createMock(EventListener::class);
        $subscription = new Subscription('id');
        $this->dispatcher->addListener($listener);

        $listener
            ->expects($this->once())
            ->method('onBeforeUpdateSubscription')
            ->with($subscription);

        $this->dispatcher->dispatch(EventType::beforeUpdateSubscription(), $subscription);
    }
}
