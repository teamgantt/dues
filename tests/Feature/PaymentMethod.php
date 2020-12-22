<?php

namespace TeamGantt\Dues\Tests\Feature;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TeamGantt\Dues\Exception\PaymentMethodNotCreatedException;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Address\State;
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
     */
    public function testCreatePaymentMethodWithBillingAddress()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->build();

        $customer = $this->dues->createCustomer($customer);
        $paymentMethod = new Nonce('fake-valid-mastercard-nonce');
        $paymentMethod->setCustomer($customer);
        $paymentMethod->setBillingAddress(new Address(State::Michigan(), '49464'));

        $paymentMethod = $this->dues->createPaymentMethod($paymentMethod);
        $this->assertInstanceOf(Address::class, $paymentMethod->getBillingAddress());
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

    /**
     * @group integration
     *
     * @return void
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCreateCustomerSession()
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
        $clientToken = $this->dues->createCustomerSession($customer->getId());

        $this->assertTrue(is_string($clientToken->getId()));
        $this->assertGreaterThan(2000, strlen($clientToken->getId()));
    }
}
