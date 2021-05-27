<?php

namespace TeamGantt\Dues\Tests\Feature;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TeamGantt\Dues\Exception\PaymentMethodNotCreatedException;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Address\Country;
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
        $paymentMethod->setBillingAddress(new Address(State::Michigan(), '49464', Country::US()));

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

    public function testCreateCustomerSessionWithOutParams()
    {
        $session = $this->dues->createCustomerSession();
        $this->assertTrue(is_string($session->getId()));
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
    public function testAddingAdditionalPaymentMethods()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->build();

        $customer = $this->dues->createCustomer($customer);

        // the first payment method is always default
        $initialMethod__default = new Nonce('fake-valid-mastercard-nonce');
        $initialMethod__default->setCustomer($customer);
        $first__default = $this->dues->createPaymentMethod($initialMethod__default);

        // adding a second payment method to ensure it's not marked as default
        $secondMethod__notDefault = new Nonce('fake-valid-visa-nonce');
        $secondMethod__notDefault->setCustomer($customer);
        $second__notDefault = $this->dues->createPaymentMethod($secondMethod__notDefault);

        // adding a third and setting as default
        $thirdMethod__default = new Nonce('fake-valid-discover-nonce');
        $thirdMethod__default->setCustomer($customer);
        $thirdMethod__default->setIsDefaultPaymentMethod(true);
        $third__default = $this->dues->createPaymentMethod($thirdMethod__default);

        $this->assertTrue($first__default->isDefaultPaymentMethod());
        $this->assertFalse($second__notDefault->isDefaultPaymentMethod());
        $this->assertTrue($third__default->isDefaultPaymentMethod());
    }
}
