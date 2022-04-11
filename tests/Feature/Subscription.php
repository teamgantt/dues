<?php

namespace TeamGantt\Dues\Tests\Feature;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Event\BaseEventListener;
use TeamGantt\Dues\Exception\InvalidPriceException;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription as ModelSubscription;
use TeamGantt\Dues\Model\Subscription\BillingPeriod;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
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

        // Ensure subscriptions are hydrated properly.
        $query = $this->dues->makeSubscriptionQuery();
        $thisSubscription = $query->getById($subscription->getId());
        $this->assertNotEmpty($thisSubscription->getCustomer()->getFirstName());
        $this->assertNotEmpty($thisSubscription->getCustomer()->getLastName());
        $this->assertNotEmpty($thisSubscription->getCustomer()->getEmailAddress());
        $this->assertGreaterThan(0, $thisSubscription->getNextBillingPeriodAmount()->getAmount());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testCreateSubscriptionWithDaysPastDue(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-yearly');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $startDate = $now->add(new DateInterval('P1D')); // start 1 day in the future

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withStartDate($startDate)
            ->withDaysPastDue(20) // this value should not be included in the request
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
    public function testVerifyASubscriptionsBillingPeriod(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-c-monthly');
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $daysInMonth = $today->format('t');

        $subscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->build();

        /**
         * @var ModelSubscription
         */
        $subscription = $this->dues->createSubscription($subscription);

        $this->assertEquals($daysInMonth, $subscription->getBillingPeriod()->getBillingCycle());
        $this->assertEquals($daysInMonth - 1, $subscription->getBillingPeriod()->getRemainingBillingCycle());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testCreateSubscriptionWithListener(callable $customerFactory)
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

        $listener = new class() extends BaseEventListener {
            public function onBeforeCreateSubscription(ModelSubscription $subscription): void
            {
                $subscription->beginImmediately(); // THERE WILL BE NO PENDING SUBS HERE!!!!
            }
        };
        $this->dues->addListener($listener);

        $subscription = $this->dues->createSubscription($subscription);

        $this->assertFalse($subscription->getCustomer()->isNew());
        $this->assertFalse($subscription->isNew());
        $this->assertEquals(Status::active(), $subscription->getStatus());
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

        $this->assertEquals('test-plan-b-yearly', $updated->getPlan()->getId());
        $this->assertEquals($plan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $previous = $subscription->toArray();
        $next = $updated->toArray();
        $this->assertEquals(Arr::dissoc($previous, ['plan', 'price']), Arr::dissoc($next, ['plan', 'price']));
    }

    /**
     * Test modifying a subscription with or without proration.
     *
     * @group integration
     * @dataProvider customerProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlanWithProration(callable $customerFactory)
    {
        // create initial subscription
        $customer = $customerFactory($this->dues);
        $plan = $this->dues->findPlanById('401m');
        $baseSubscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withAddOn(new AddOn('401m-u', 1))
            ->build();

        $subscription = $this->dues->createSubscription($baseSubscription);

        $this->assertEquals(count($subscription->getTransactions()), 1);

        // upgrades with proration cause additional charge
        $addOn = $subscription->getAddOns()[0];
        $addOn->setQuantity(3);
        $prorated = $this->dues->updateSubscription($subscription);

        $this->assertEquals(count($prorated->getTransactions()), 2);

        // upgrades without prorations do not charge the subscription
        $addOn = $prorated->getAddOns()[0];
        $addOn->setQuantity(10);
        $subscription->setIsProrated(false);
        $notProrated = $this->dues->updateSubscription($subscription);

        $this->assertEquals(count($notProrated->getTransactions()), 2);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlanToPlanWithDifferentBillingCycle(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (ModelSubscription $s) => $s->beginImmediately());
        $plan = $this->dues->findPlanById('test-plan-c-monthly');
        $subscription->setPlan($plan);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals('test-plan-c-monthly', $updated->getPlan()->getId());
        $this->assertEquals($plan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $this->assertGreaterThan(0, count($updated->getDiscounts()));
        $this->assertTrue($updated->is(Status::active()));
        $this->assertTrue($subscription->is(Status::canceled()));
        $balance = $updated->getBalance()->getAmount();
        $price = $updated->getPrice()->getAmount();
        $addOnPrice = $updated->getAddOns()[0]->getPrice()->getAmount();
        $rollover = $subscription->getRemainingValue()->getAmount();
        $this->assertEquals($price + $addOnPrice - $rollover, $balance);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlanToPlanWithDifferentBillingCycleFromYearly(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, function (ModelSubscription $s) {
            $s->beginImmediately();
            $plan = $this->dues->findPlanById('test-plan-c-yearly');
            $plan->setPrice(new Price(1731.57));
            $s->setAddOns(new Modifiers());

            return $s->resetPlan($plan);
        });

        $nextPlan = $this->dues->findPlanById('test-plan-c-monthly');
        $nextPlan->setPrice(new Price(64.75));
        $nextSubscription = $subscription->setPlan($nextPlan);
        $updated = $this->dues->updateSubscription($nextSubscription);

        $this->assertEquals('test-plan-c-monthly', $updated->getPlan()->getId());
        $this->assertEquals($nextPlan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $this->assertGreaterThan(0, count($updated->getDiscounts()));
        $this->assertTrue($updated->is(Status::active()));
        $this->assertTrue($subscription->is(Status::canceled()));

        $rollover = $subscription->getRemainingValue()->getAmount();
        $this->assertEquals($rollover, 1726.83);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateSubscriptionPlanToPlanWithDifferentBillingCycleWhenAddOnNotPreviouslyAssociatedWithPlan(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, function (ModelSubscription $s) {
            $s->beginImmediately();
            $plan = $this->dues->findPlanById('401m');
            $s->setAddOns(new Modifiers());

            return $s->resetPlan($plan);
        });

        $plan = $this->dues->findPlanById('401y');
        $subscription->setPlan($plan);

        // ensures we can add a new quantity for a defaulted plan addon
        $newAddOn = new AddOn('401y-u', 2);
        $subscription->addAddOn($newAddOn);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals('401y', $updated->getPlan()->getId());
        $this->assertEquals($plan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $this->assertGreaterThan(0, count($updated->getDiscounts()));
        $this->assertEquals('401y-u', $updated->getAddOns()[0]->getId());
        $this->assertEquals(2, $updated->getAddOns()[0]->getQuantity());
        $this->assertTrue($updated->is(Status::active()));
        $this->assertTrue($subscription->is(Status::canceled()));
        $balance = $updated->getBalance()->getAmount();
        $this->assertEquals(0.0, $balance);
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateWithSubscriptionWithPlanAddOnsToPlanWithoutAddOns(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, function (ModelSubscription $s) {
            $s->beginImmediately();
            $plan = $this->dues->findPlanById('401m');
            $s->setAddOns(new Modifiers());

            return $s->resetPlan($plan);
        });

        $plan = $this->dues->findPlanById('001');
        $subscription->setPlan($plan);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals('001', $updated->getPlan()->getId());
        $this->assertEquals($plan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $this->assertEquals(0, count($updated->getAddOns()));
        $this->assertTrue($updated->is(Status::active()));
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testUpdateWithSubscriptionWithoutPlanAddOnsToPlanWithAddOns(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, function (ModelSubscription $s) {
            $s->beginImmediately();
            $plan = $this->dues->findPlanById('001');
            $s->setAddOns(new Modifiers());

            return $s->resetPlan($plan);
        });

        $plan = $this->dues->findPlanById('401m');
        $subscription->setPlan($plan);
        $subscription->addAddOn(new AddOn('401m-u', 2, new Price(10.0)));

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals('401m', $updated->getPlan()->getId());
        $this->assertEquals('401m-u', $updated->getAddOns()[0]->getId());
        $this->assertEquals($plan->getPrice()->getAmount(), $updated->getPrice()->getAmount());
        $this->assertEquals(1, count($updated->getAddOns()));
        $this->assertTrue($updated->is(Status::active()));
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

        $this->assertEquals('test-plan-b-yearly', $updated->getPlan()->getId());
        $this->assertEquals(20.00, $updated->getPrice()->getAmount());
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
    public function testUpdateSubscriptionPriceAndPlanViaListener(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $plan = $this->dues->findPlanById('test-plan-b-yearly');
        $subscription->setPlan($plan);

        $listener = new class() extends BaseEventListener {
            public function onBeforeUpdateSubscription(ModelSubscription $subscription): void
            {
                $subscription->setPrice(new Price(20.00));
            }
        };
        $this->dues->addListener($listener);

        $updated = $this->dues->updateSubscription($subscription);

        $this->assertEquals('test-plan-b-yearly', $updated->getPlan()->getId());
        $this->assertEquals(20.00, $updated->getPrice()->getAmount());
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
        $this->assertNotEmpty($subscription->getTransactions());
        $this->assertEquals(0, $subscription->getDaysPastDue());
        $this->assertNotNull($subscription->getBillingPeriod());

        $this->assertNotEmpty($subscription->getStatusHistory());
        $this->assertEquals(Status::active(), $subscription->getStatusHistory()[0]->getStatus());
        $this->assertEquals('test-plan-c-yearly', $subscription->getStatusHistory()[0]->getPlanId());
        $this->assertEquals(new Money(0), $subscription->getStatusHistory()[0]->getBalance());
        $this->assertEquals(new Money(150), $subscription->getStatusHistory()[0]->getPrice());
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
    public function testFindSubscriptionsByCustomerIdWithStatusFilter(callable $subscriptionFactory)
    {
        $pending = $subscriptionFactory($this->dues); // create a pending subscription
        $customer = $pending->getCustomer();
        $subscriptionFactory($this->dues, $customer, fn (ModelSubscription $sub) => $sub->beginImmediately()); // create an active subscription
        $canceled = $subscriptionFactory($this->dues, $customer);
        $canceled = $this->dues->cancelSubscription($canceled->getId()); // create a canceled subscription
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customer->getId(), [Status::active(), Status::pending()]);
        $this->assertCount(2, $subscriptions);
        $this->assertEquals(Status::pending(), $subscriptions[0]->getStatus());
        $this->assertEquals(Status::active(), $subscriptions[1]->getStatus());
        $this->assertNotNull($customer->getDefaultPaymentMethod()->getExpirationDate());
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
     * @dataProvider subscriptionProvider
     * @group integration
     */
    public function testCancelSubscriptions(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscriptions = $this->dues->cancelSubscriptions([$subscription]);
        $this->assertEquals(Status::canceled(), $subscriptions[0]->getStatus());
    }

    /**
     * @dataProvider subscriptionProvider
     * @group integration
     */
    public function testCancelSubscriptionsWithCanceledSubscriptions(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues);
        $subscription = $this->dues->cancelSubscription($subscription->getId());
        $subscriptions = $this->dues->cancelSubscriptions([$subscription]);
        $this->assertEmpty($subscriptions);
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

    public function testSubscriptionSearchWithDaysPastDue()
    {
        $query = $this->dues->makeSubscriptionQuery();
        $overdue = $query->whereDaysPastDue('>=', 1)->fetch();

        // this assumes that you have at least one past due subscription in your processor.
        // for the case of braintree, you can create a past due subscription by using an
        // invalid payment method `fake-invalid-nonce` and setting a start date in the future.
        // The subscription will fail to start, and immediately go into an past due status.
        $this->assertGreaterThan(0, $overdue);

        // should be an unreachable case, but we're verifying that the query works
        $overdue = $query->whereDaysPastDue('<=', -1)->fetch();
        $this->assertEquals(0, count($overdue));

        // should be an unreachable case, but we're verifying that the query works
        $overdue = $query->whereDaysPastDue('=', -2)->fetch();
        $this->assertEquals(0, count($overdue));
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testSubscriptionUpgradeCrossPlanWithUpdateToDefaultAddOns(callable $customerFactory)
    {
        $customer = $customerFactory($this->dues);

        // Create initial subscription
        $plan = $this->dues->findPlanById('401m');
        $baseSubscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withAddOn(new AddOn('401m-u', 8))
            ->build();

        $baseSubscription = $this->dues->createSubscription($baseSubscription);
        $customer = $baseSubscription->getCustomer();
        $customerId = $customer->getId();

        // Load subscription from Braintree
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customerId);
        $originalSubscription = $subscriptions[0];

        // Set new plan
        $newPlan = $this->dues->findPlanById('402m');
        $originalSubscription->setPlan($newPlan);

        // Update default modifier
        $originalSubscription->addAddOn(new AddOn('402m-u', 8));

        // Add new modifier
        $originalSubscription->addAddOn(new AddOn('sales_tax', 1, new Price(1.00)));

        $updatedSubscription = $this->dues->updateSubscription($originalSubscription);

        $addOns = $updatedSubscription->getAddOns();
        $salesTaxAddOn = Arr::filter($addOns, fn ($addOn) => 'sales_tax' === $addOn->getId())[0];
        $usersAddOn = Arr::filter($addOns, fn ($addOn) => '402m-u' === $addOn->getId())[0];

        $this->assertEquals(8, $usersAddOn->getQuantity());
        $this->assertEquals(1, $salesTaxAddOn->getQuantity());
    }

    /**
     * @group integration
     * @dataProvider customerProvider
     */
    public function testChangingNonDefaultAddonPrice(callable $customerFactory)
    {
        $originalPrice = 27.06;
        $newPrice = 2.06;

        $customer = $customerFactory($this->dues);

        // Create initial subscription
        $plan = $this->dues->findPlanById('401m');
        $baseSubscription = (new SubscriptionBuilder())
            ->withCustomer($customer)
            ->withPlan($plan)
            ->withAddOn(new AddOn('401m-u', 8))
            ->withAddOn(new AddOn('sales_tax', 1, new Price($originalPrice)))
            ->build();

        $baseSubscription = $this->dues->createSubscription($baseSubscription);
        $customer = $baseSubscription->getCustomer();
        $customerId = $customer->getId();

        $salesTaxAddOn = Arr::filter($baseSubscription->getAddOns(), fn ($addOn) => 'sales_tax' === $addOn->getId())[0];
        $this->assertEquals($originalPrice, $salesTaxAddOn->getPrice()->getAmount());

        // Load subscription from Braintree
        $subscriptions = $this->dues->findSubscriptionsByCustomerId($customerId);
        $originalSubscription = $subscriptions[0];

        // Update sales tax
        $originalSubscription->addAddOn(new AddOn('sales_tax', 1, new Price($newPrice)));

        $updatedSubscription = $this->dues->updateSubscription($originalSubscription);

        $salesTaxAddOn = Arr::filter($updatedSubscription->getAddOns(), fn ($addOn) => 'sales_tax' === $addOn->getId())[0];
        $this->assertEquals($newPrice, $salesTaxAddOn->getPrice()->getAmount());
    }

    public function testSubscriptionGetValueIsZeroWithoutPrice()
    {
        $subscription = new ModelSubscription();

        $value = $subscription->getValue();

        $this->assertEquals(0.0, $value->getAmount());
    }

    public function testSubscriptionGetValueWithPrice()
    {
        $subscription = new ModelSubscription();
        $price = new Price(3.50);
        $subscription->setPrice($price);

        $value = $subscription->getValue();

        $this->assertEquals(3.50, $value->getAmount());
    }

    public function testSubscriptionGetValueWithAddOns()
    {
        $subscription = new ModelSubscription();
        $price = new Price(3.50);
        $subscription->setPrice($price);
        $subscription->addAddOn(new AddOn('test', 2, new Price(2.00)));

        $value = $subscription->getValue();

        $this->assertEquals(7.50, $value->getAmount());
    }

    public function testSubscriptionGetValueWithDiscounts()
    {
        $subscription = new ModelSubscription();
        $price = new Price(3.50);
        $subscription->setPrice($price);
        $subscription->addDiscount(new Discount('test', 2, new Price(1.00)));

        $value = $subscription->getValue();

        $this->assertEquals(1.50, $value->getAmount());
    }

    public function testSubscriptionGetValueWithAddOnsAndDiscounts()
    {
        $subscription = new ModelSubscription();
        $price = new Price(3.50);
        $subscription->setPrice($price);
        $subscription->addDiscount(new Discount('test', 2, new Price(1.00)));
        $subscription->addAddOn(new AddOn('test-2', 3, new Price(2.00)));

        $value = $subscription->getValue();

        $this->assertEquals(7.50, $value->getAmount());
    }

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testSubscriptionValuesWhenSubscriptionHasBalance()
    {
        // Guarantee that we are always 1 day into a 30 day subscription.
        $today = new DateTimeImmutable('UTC');
        $subscriptionStart = $today->modify('-1 day');
        $subscriptionEnd = $today->modify('+28 days');

        $subscription = new ModelSubscription();
        $price = new Price(15.00);
        $subscription->setPrice($price);
        $discount = new Discount('balance', 1, new Price(384.04));
        $discount->setIsExpired(true);
        $subscription->addDiscount($discount);
        $subscription->addAddOn(new AddOn('test-2', 3, new Price(9.95)));
        $subscription->setBillingPeriod(
            new BillingPeriod($subscriptionStart, $subscriptionEnd)
        );
        $subscription->setBalance(new Money(-339.19));

        $calculator = new class() extends SubscriptionMapper {
            public function __construct()
            {
                // Override mapper constructor
            }

            public function exposeSetRemainingValue(ModelSubscription $subscription): ModelSubscription
            {
                return $this->setRemainingValue($subscription);
            }
        };

        $hydratedSubscription = $calculator->exposeSetRemainingValue($subscription);

        $this->assertEquals(30, $hydratedSubscription->getBillingPeriod()->getBillingCycle());
        $this->assertEquals(29, $hydratedSubscription->getBillingPeriod()->getRemainingBillingCycle());
        $this->assertEquals(-339.19, $hydratedSubscription->getBalance()->getAmount());
        $this->assertEquals(44.85, $hydratedSubscription->getValue()->getAmount());
        $this->assertEquals(43.36, $hydratedSubscription->getRemainingValue()->getAmount());
    }
}
