<?php

namespace TeamGantt\Dues\Model\Tests;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;

final class CustomerTest extends TestCase
{
    public function testCannotAddPaymentMethodTwice()
    {
        $customer = new Customer();
        $paymentMethod = new Nonce('nonce');

        $customer->addPaymentMethod($paymentMethod);
        $customer->addPaymentMethod($paymentMethod);

        $this->assertCount(1, $customer->getPaymentMethods());
    }

    public function testAddingDefaultPaymentMethod()
    {
        $customer = new Customer();
        $paymentMethod = (new Nonce('default'))->setIsDefaultPaymentMethod(true);
        $customer->addPaymentMethod($paymentMethod);

        $default = $customer->getDefaultPaymentMethod();

        $this->assertTrue($default->isEqualTo($paymentMethod));
    }

    public function testAddingSecondDefaultPaymentMethodReplacesFirst()
    {
        $customer = new Customer();
        $paymentMethod = (new Nonce('original-default'))->setIsDefaultPaymentMethod(true);
        $customer->addPaymentMethod($paymentMethod);
        $newDefault = (new Nonce('new-default'))->setIsDefaultPaymentMethod(true);
        $customer->addPaymentMethod($newDefault);

        $default = $customer->getDefaultPaymentMethod();

        $this->assertTrue($default->isEqualTo($newDefault));
    }
}
