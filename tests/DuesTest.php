<?php

namespace TeamGantt\Dues\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Dues;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;

final class DuesTest extends TestCase
{
    private Dues $dues;

    /**
     * @var SubscriptionGateway|MockObject
     */
    private $gateway;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(SubscriptionGateway::class);
        $this->dues = new Dues($this->gateway);
    }

    public function testRollingBackNewCustomerWhenNewSubscriptionFails()
    {
        // account for the rethrow
        $this->expectException(SubscriptionNotCreatedException::class);

        $subscription = new Subscription();
        $customer = new Customer('sounique');

        $this->gateway
            ->expects($this->once())
            ->method('createSubscription')
            ->with($subscription)
            ->willThrowException(new SubscriptionNotCreatedException('oops!', $customer));

        // The customer should be deleted if createSubscription throws an exception
        $this->gateway
            ->expects($this->once())
            ->method('deleteCustomer')
            ->with($customer->getId());

        $this->dues->createSubscription($subscription);
    }

    public function testChangePaymentMethodUpdatesAppropriateSubscriptionsOnly()
    {
        $active = (new Subscription())->setStatus(Status::active());
        $pending = (new Subscription())->setStatus(Status::pending());
        $pastDue = (new Subscription())->setStatus(Status::pastDue());
        $canceled = (new Subscription())->setStatus(Status::canceled());
        $expired = (new Subscription())->setStatus(Status::expired());
        $customer = new Customer('blorp');
        $paymentMethod = new Nonce('nonce');

        $this->gateway
            ->expects($this->once())
            ->method('updateCustomer')
            ->with($this->identicalTo($customer))
            ->willReturn($customer);

        $this->gateway
            ->expects($this->once())
            ->method('findSubscriptionsByCustomerId')
            ->willReturn([$active, $pending, $pastDue, $canceled, $expired]);

        $this->gateway
            ->expects($this->exactly(3))
            ->method('updateSubscription')
            ->withConsecutive(
                [$this->identicalTo($active)],
                [$this->identicalTo($pending)],
                [$this->identicalTo($pastDue)]
            );

        $this->dues->changePaymentMethod($customer, $paymentMethod);
    }
}
