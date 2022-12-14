<?php

namespace TeamGantt\Dues\Tests\Feature;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TeamGantt\Dues\Event\BaseEventListener;
use TeamGantt\Dues\Exception\CustomerNotCreatedException;
use TeamGantt\Dues\Model\Customer as ModelCustomer;
use TeamGantt\Dues\Model\Customer\CustomerBuilder;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Tests\ProvidesTestData;

trait Customer
{
    use ProvidesTestData;

    /**
     * @group integration
     *
     * @return void
     */
    public function testCreateCustomer()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->build();

        $customer = $this->dues->createCustomer($customer);

        $this->assertFalse($customer->isNew());
        $this->assertEquals('Bill', $customer->getFirstName());
        $this->assertEquals('Steffen', $customer->getLastName());
        $this->assertEquals('bill.steffen@email.com', $customer->getEmailAddress());
    }

    /**
     * @group integration
     *
     * @return void
     */
    public function testCreateCustomerWithListener()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->build();

        $listener = new class() extends BaseEventListener {
            public function onBeforeCreateCustomer(ModelCustomer $customer): void
            {
                $customer->setFirstName('William');
            }
        };
        $this->dues->addListener($listener);

        $customer = $this->dues->createCustomer($customer);
        $this->assertFalse($customer->isNew());
        $this->assertEquals('William', $customer->getFirstName());
        $this->assertEquals('Steffen', $customer->getLastName());
        $this->assertEquals('bill.steffen@email.com', $customer->getEmailAddress());
    }

    /**
     * @group integration
     *
     * @return void
     */
    public function testCreateCustomerWithPaymentMethods()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->withPaymentMethod(new Nonce('fake-valid-mastercard-nonce'))
            ->build();

        $customer = $this->dues->createCustomer($customer);

        $this->assertFalse($customer->isNew());
        $this->assertEquals('Bill', $customer->getFirstName());
        $this->assertEquals('Steffen', $customer->getLastName());
        $this->assertEquals('bill.steffen@email.com', $customer->getEmailAddress());
        $this->assertCount(1, $customer->getPaymentMethods());
        $this->assertInstanceOf(Token::class, $customer->getPaymentMethods()[0]);
        $this->assertNotNull($customer->getDefaultPaymentMethod());
    }

    /**
     * @group integration
     *
     * @return void
     */
    public function testCreateInvalidCustomer()
    {
        $this->expectException(CustomerNotCreatedException::class);

        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen')
            ->build();

        $customer = $this->dues->createCustomer($customer);
    }

    /**
     * @group integration
     *
     * @dataProvider customerByValidityProvider
     *
     * @param string $id
     *
     * @return void
     */
    public function testFindCustomerById(callable $idFn, bool $isNull)
    {
        $id = $idFn($this->dues);
        $customer = $this->dues->findCustomerById($id);

        if ($isNull) {
            $this->assertNull($customer);

            return;
        }

        $this->assertNotNull($customer);
        $this->assertNotEmpty($customer->getEmailAddress());
        $this->assertNotEmpty($customer->getFirstName());
        $this->assertNotEmpty($customer->getLastName());
        $this->assertFalse($customer->isNew());
        $this->assertNotNull($customer->getDefaultPaymentMethod());
    }

    /**
     * @group integration
     *
     * @dataProvider customerByValidityProvider
     *
     * @return void
     */
    public function testDeleteCustomer(callable $idFn, bool $isNull)
    {
        $id = $idFn($this->dues);

        if ($isNull) {
            $this->markTestSkipped('Skipping delete of non-existent customer');

            return;
        }

        $this->dues->deleteCustomer($id);

        $this->assertNull($this->dues->findCustomerById($id));
    }

    /**
     * @group integration
     *
     * @dataProvider customerByValidityProvider
     *
     * @return void
     */
    public function testAddPaymentMethod(callable $idFn, bool $isNull)
    {
        if ($isNull) {
            $this->markTestSkipped('Skipping update of non-existent customer');

            return;
        }

        $id = $idFn($this->dues);
        $customer = $this->dues->findCustomerById($id);
        $newPaymentMethod = new Nonce('fake-valid-visa-nonce');
        $customer->addPaymentMethod($newPaymentMethod);
        $updated = $this->dues->updateCustomer($customer);
        $this->assertCount(2, $updated->getPaymentMethods());
    }

    /**
     * @group integration
     *
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testChangePaymentMethodToExistingPaymentMethod(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $customer = $subscription->getCustomer();
        $nonDefault = (new Nonce('fake-valid-visa-nonce'))->setCustomer($customer);
        $token = $this->dues->createPaymentMethod($nonDefault);

        $customer = $this->dues->changePaymentMethod($customer, $token);
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customer->getId());

        $this->assertTrue($customer->getDefaultPaymentMethod()->isEqualTo($token));
        foreach ($subscriptions as $subscription) {
            $this->assertTrue($subscription->getPaymentMethod()->isEqualTo($token));
        }
    }

    /**
     * @group integration
     *
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testChangePaymentMethodToNewPaymentMethod(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $customer = $subscription->getCustomer();
        $currentDefault = $customer->getDefaultPaymentMethod();
        $nonDefault = (new Nonce('fake-valid-visa-nonce'))->setCustomer($customer);

        $customer = $this->dues->changePaymentMethod($customer, $nonDefault);
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customer->getId());

        $this->assertNotNull($currentDefault);
        $this->assertFalse($customer->getDefaultPaymentMethod()->isEqualTo($currentDefault));
        $this->assertFalse($subscriptions[0]->getPaymentMethod()->isEqualTo($subscription->getPaymentMethod()));
    }

    /**
     * @group integration
     *
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateCustomerWithListener(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $customer = $subscription->getCustomer();
        $customer->setFirstName('NotBlorpus');

        $listener = new class() extends BaseEventListener {
            public function onBeforeUpdateCustomer(ModelCustomer $customer): void
            {
                $customer->setFirstName('Blorpus');
            }
        };
        $this->dues->addListener($listener);

        $updated = $this->dues->updateCustomer($customer);
        $this->assertEquals('Blorpus', $updated->getFirstName());
    }

    /**
     * @group integration
     *
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testChangePaymentMethodToExistingPaymentMethodWithCancelledSubscriptions(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $customer = $subscription->getCustomer();
        $nonDefault = (new Nonce('fake-valid-visa-nonce'))->setCustomer($customer);
        $token = $this->dues->createPaymentMethod($nonDefault);
        $this->dues->cancelSubscription($subscription->getId());

        $customer = $this->dues->changePaymentMethod($customer, $token);
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customer->getId());

        $this->assertTrue($customer->getDefaultPaymentMethod()->isEqualTo($token));
        $this->assertTrue($subscriptions[0]->getPaymentMethod()->isEqualTo($subscription->getPaymentMethod()));
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
    public function testFindCustomerByIdWithStoredPaymentMethods()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Bill')
            ->withLastName('Steffen')
            ->withEmailAddress('bill.steffen@email.com')
            ->withPaymentMethod(new Nonce('fake-valid-mastercard-nonce'))
            ->build();

        $customer = $this->dues->createCustomer($customer);

        $customer = $this->dues->findCustomerById($customer->getId());

        $this->assertCount(1, $customer->getPaymentMethods());
        $this->assertInstanceOf(Token::class, $customer->getPaymentMethods()[0]);
    }
}
