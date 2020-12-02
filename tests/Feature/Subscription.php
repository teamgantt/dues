<?php

namespace TeamGantt\Dues\Tests\Feature;

use DateInterval;
use DateTime;
use DateTimeZone;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Exception\InvalidPriceException;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription\AddOn;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Tests\ProvidesTestData;

trait Subscription
{
    use ProvidesTestData;

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testCreatePendingSubscription(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->add(new DateInterval('P1D')); // start 1 day in the future

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $this->assertFalse($subscription->getCustomer()->isNew());
        $this->assertFalse($subscription->isNew());
        $this->assertEquals(Status::pending(), $subscription->getStatus());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testModifyingDefaultSubscriptionAddOn(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-d-yearly');
        $addOn = new AddOn('test-plan-d-yearly-u', 2);

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withAddOn($addOn)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $this->assertFalse($subscription->isNew());
        $this->assertEquals(2, $subscription->getAddOns()[0]->getQuantity());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testModifyingDefaultSubscriptionDiscount(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-d-yearly');
        $discount = new Discount('test-plan-d-yearly-discount', 2);

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withDiscount($discount)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $this->assertFalse($subscription->isNew());
        $this->assertEquals(2, $subscription->getDiscounts()[0]->getQuantity());
    }

    /**
     * In a perfect world, changing a plan would also update the price. This is at least
     * not the case with Braintree. This assertion may only make sense in the context
     * of Braintree since Braintree does NOT update price when a plan changes.
     *
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlan(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-b-yearly');
        $subscription->setPlan($plan);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getPlan()->getId(), 'test-plan-b-yearly');
        $this->assertEquals($updated->getPrice()->getAmount(), $plan->getPrice()->getAmount());
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['plan', 'price']), Arr::dissoc($next, ['plan', 'price']));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPriceAndPlan(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-b-yearly');
        $subscription->setPlan($plan);
        $subscription->setPrice(new Price(20.00));

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getPlan()->getId(), 'test-plan-b-yearly');
        $this->assertEquals($updated->getPrice()->getAmount(), 20.00);
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['plan', 'price']), Arr::dissoc($next, ['plan', 'price']));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlanToNonExistentPlan(callable $subscriptionFactory)
    {
        $this->expectException(SubscriptionNotUpdatedException::class);
        $subscription = $subscriptionFactory($this->dues);
        $plan = new Plan('foobarbaz');
        $subscription->setPlan($plan);

        $this->dues->updateSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionBasePrice(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscription->setPrice(new Price(20.00));

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getPrice()->getAmount(), 20.00);
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['price']), Arr::dissoc($next, ['price']));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionWithBasePriceOfZero(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscription->setPrice(new Price(0.00));

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getPrice()->getAmount(), 0.00);
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['price']), Arr::dissoc($next, ['price']));
    }

    /**
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionWithNegativePrice(callable $subscriptionFactory)
    {
        $this->expectException(InvalidPriceException::class);
        $subscription = $subscriptionFactory($this->dues);
        $subscription->setPrice(new Price(-10.00));

        $this->dues->updateSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionAddOnQuantity(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $addOn = $subscription->getAddOns()[0];
        $newQuantity = $addOn->getQuantity() + 1;
        $addOn->setQuantity($newQuantity);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getAddOns()[0]->getQuantity(), $newQuantity);
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['addOns']), Arr::dissoc($next, ['addOns']));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionWithNewAddOn(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $addOns = $this->dues->listAddOns();
        $newAddOn = $addOns[1];
        $newAddOn->setQuantity(3);
        $subscription->addAddOn($newAddOn);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals($updated->getAddOns()[1]->getQuantity(), $newAddOn->getQuantity());
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['addOns']), Arr::dissoc($next, ['addOns']));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateWithRemovedAddOn(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $addOn = $subscription->getAddOns()[0];
        $subscription->removeAddOn($addOn->getId());

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertCount(0, $updated->getAddOns());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testCreateSubscriptionWithoutStartDate(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $this->assertFalse($subscription->getCustomer()->isNew());
        $this->assertFalse($subscription->isNew());
        $this->assertEquals(Status::active(), $subscription->getStatus());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testCreatePendingSubscriptionWithAddOn(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $addOns = $this->dues->listAddOns();
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->add(new DateInterval('P1D')); // start 1 day in the future

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->withAddOn($addOns[0])
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $newAddOns = $subscription->getAddOns();
        $this->assertNotEmpty($newAddOns);
        $this->assertEquals($addOns[0]->getId(), $newAddOns[0]->getId());
        $this->assertEquals(Status::pending(), $subscription->getStatus());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testCreateSubscriptionWithDiscount(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $discounts = $this->dues->listDiscounts();

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withDiscount($discounts[0])
            ->build();

        $subscription = $this->dues->createSubscription($subscription);

        $newDiscounts = $subscription->getDiscounts();
        $this->assertNotEmpty($newDiscounts);
        $this->assertEquals($discounts[0]->getId(), $newDiscounts[0]->getId());
        $this->assertEquals(Status::active(), $subscription->getStatus());
    }

    /**
     * @group integration
     */
    public function testCreateSubscriptionWithInvalidData()
    {
        $this->expectException(SubscriptionNotCreatedException::class);

        $subscription = (new SubscriptionBuilder())->build();

        $this->dues->createSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testCreateSubscriptionWithInvalidAddOn(callable $customerFactory)
    {
        $this->expectException(SubscriptionNotCreatedException::class);

        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $addOn = new AddOn('totally-made-up-yall');

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withAddOn($addOn)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testCreateSubscriptionWithInvalidDiscount(callable $customerFactory)
    {
        $this->expectException(SubscriptionNotCreatedException::class);

        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->add(new DateInterval('P1D')); // start 1 day in the future
        $discount = new Discount('totally-made-up-yall');

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->withDiscount($discount)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testCreateSubscriptionWithInvalidStartDate(callable $customerFactory)
    {
        $this->expectException(SubscriptionNotCreatedException::class);
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->sub(new DateInterval('P1D')); // start 1 day in the past

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->build();

        $subscription = $this->dues->createSubscription($subscription);
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testFailedSubscriptionWithNewUserRollsBackUser(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);

        if (!$customer->isNew()) {
            $this->markTestSkipped('Cannot rollback an existing user');
        }

        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->sub(new DateInterval('P1D')); // start 1 day in the past

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->build();

        try {
            $this->dues->createSubscription($subscription);
        } catch (SubscriptionNotCreatedException $e) {
            $id = $subscription->getCustomer()->getId();
            $customer = $this->dues->findCustomerById($id);
            $this->assertNull($customer);
        }
    }

    /**
     * @dataProvider subscriptionProvider
     * @group integration
     */
    public function testFindSubscriptionsByCustomerId(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $customer = $subscription->getCustomer();
        $this->assertNotNull($customer);
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customer->getId());
        $this->assertCount(1, $subscriptions);
    }

    /**
     * @dataProvider subscriptionProvider
     * @group integration
     */
    public function testFindSubscription(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscription = $this->dues->findSubscriptionById($subscription->getId());
        $this->assertNotNull($subscription);
    }

    /**
     * @dataProvider subscriptionProvider
     * @group integration
     */
    public function testCancelSubscription(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscription = $this->dues->cancelSubscription($subscription->getId());
        $this->assertEquals(Status::canceled(), $subscription->getStatus());
    }

    /**
     * @group integration
     */
    public function testListingAddons()
    {
        $addOns = $this->dues->listAddOns();

        $this->assertNotEmpty($addOns);
        $this->assertNotNull($addOns[0]->getPrice());
        $this->assertNotEmpty($addOns[0]->getId());
    }

    /**
     * @group integration
     */
    public function testListingDiscounts()
    {
        $discounts = $this->dues->listDiscounts();

        $this->assertNotEmpty($discounts);
        $this->assertNotNull($discounts[0]->getPrice());
        $this->assertNotEmpty($discounts[0]->getId());
    }
}
