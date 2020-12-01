<?php

namespace TeamGantt\Dues\Tests\Feature;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TeamGantt\Dues\Exception\PaymentMethodNotCreatedException;
use TeamGantt\Dues\Model\Customer\CustomerBuilder;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\PaymentMethod\Token;

trait PaymentMethod
{
    /**
     * @group integration
     *
     * @return void
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCreatePaymentMethod()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->build();

        $customer = $this->dues->createCustomer($customer);
        $paymentMethod = new Nonce('fake-valid-mastercard-nonce');
        $paymentMethod->setCustomer($customer);

        $paymentMethod = $this->dues->createPaymentMethod($paymentMethod);
        $this->assertInstanceOf(Token::class, $paymentMethod);
    }

    /**
     * @group integration
     *
     * @return void
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCreatePaymentMethodWithoutCustomer()
    {
        $this->expectException(PaymentMethodNotCreatedException::class);
        $paymentMethod = new Nonce('fake-valid-mastercard-nonce');

        $paymentMethod = $this->dues->createPaymentMethod($paymentMethod);
        $this->assertInstanceOf(Token::class, $paymentMethod);
    }
}
