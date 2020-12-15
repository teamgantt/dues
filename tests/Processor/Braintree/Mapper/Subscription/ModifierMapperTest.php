<?php

namespace TeamGantt\Dues\Tests\Processor\Braintree\Mapper\Subscription;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Mapper\Subscription\ModifierMapper;

final class ModifierMapperTest extends TestCase
{
    private ModifierMapper $modifierMapper;

    private Plan $plan;

    protected function setUp(): void
    {
        $this->modifierMapper = new ModifierMapper();
        $plan = new Plan('default-plan');
        $defaultAddOn = new AddOn('default-addon-1');
        $plan->addAddOn($defaultAddOn);
        $defaultDiscount = new Discount('default-discount-1');
        $plan->addDiscount($defaultDiscount);
        $this->plan = $plan;
    }

    public function testToRequestAddOnsForNewSubscriptionAddsNonPlanDefaultsAsAdded()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $nonDefault = new AddOn('non-default', 2, new Price(2.50));
        $subscription->addAddOn($nonDefault);

        list($addOns) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['add' => [[
            'quantity' => 2,
            'amount' => 2.5,
            'inheritedFromId' => 'non-default',
        ]]], $addOns);
    }

    public function testToRequestDiscountForNewSubscriptionAddsNonPlanDefaultsAsAdded()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $nonDefault = new Discount('non-default', 2, new Price(2.50));
        $subscription->addDiscount($nonDefault);

        list(, $discounts) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['add' => [[
            'quantity' => 2,
            'amount' => 2.5,
            'inheritedFromId' => 'non-default',
        ]]], $discounts);
    }

    public function testToRequestAddOnsForNewSubscriptionSkipsAddOnMatchingDefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $defaultDuplicate = new AddOn('default-addon-1');
        $subscription->addAddOn($defaultDuplicate);

        list($addOns) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEmpty($addOns);
    }

    public function testToRequestDiscountsForNewSubscriptionSkipsDiscountMatchingDefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $defaultDuplicate = new Discount('default-discount-1');
        $subscription->addDiscount($defaultDuplicate);

        list(, $discounts) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEmpty($discounts);
    }

    public function testToRequestAddOnsForNewSubscriptionSupportsRemovingDefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $subscription->removeAddOn('default-addon-1');

        list($addOns) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['remove' => ['default-addon-1']], $addOns);
    }

    public function testToRequestDiscountsForNewSubscriptionSupportsRemovingDefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $subscription->removeDiscount('default-discount-1');

        list(, $discounts) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['remove' => ['default-discount-1']], $discounts);
    }

    public function testToRequestAddOnsForNewSubscriptionSupportsUpdatingADefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $subscription->addAddOn(new AddOn('default-addon-1', 2, new Price(5.00)));

        list($addOns) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['update' => [[
            'quantity' => 2,
            'amount' => 5.00,
            'existingId' => 'default-addon-1',
        ]]], $addOns);
    }

    public function testToRequestDiscountsForNewSubscriptionSupportsUpdatingADefault()
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan);
        $subscription->addDiscount(new Discount('default-discount-1', 2, new Price(5.00)));

        list(, $discounts) = $this->modifierMapper->toRequest($subscription, $this->plan);

        $this->assertEquals(['update' => [[
            'quantity' => 2,
            'amount' => 5.00,
            'existingId' => 'default-discount-1',
        ]]], $discounts);
    }

    public function testToRequestAddOnsForExistingSubscriptionRemovesPreviousPlanDefaults()
    {
        $subscription = new Subscription('existing');
        $subscription->setPlan($this->plan);
        $newPlan = new Plan('next-plan');
        $defaultAddOn = new AddOn('next-plan-default-addon-1');
        $defaultAddOn->setPrice(new Price(10.00));
        $defaultAddOn->setQuantity(10);
        $newPlan->addAddOn($defaultAddOn);
        $subscription->setPlan($newPlan);
        $nonDefault = new AddOn('non-default', 2, new Price(2.50));
        $subscription->addAddOn($nonDefault);

        list($addOns) = $this->modifierMapper->toRequest($subscription, $newPlan);

        $this->assertEquals([
            'remove' => ['default-addon-1'],
            'add' => [
                [
                    'quantity' => 2,
                    'amount' => 2.50,
                    'inheritedFromId' => 'non-default',
                ],
                [
                    'quantity' => 10,
                    'amount' => 10.00,
                    'inheritedFromId' => 'next-plan-default-addon-1',
                ],
            ],
        ], $addOns);
    }

    public function testToRequestDiscountsForExistingSubscriptionRemovesPreviousPlanDefaults()
    {
        $subscription = new Subscription('existing');
        $subscription->setPlan($this->plan);
        $newPlan = new Plan('next-plan');
        $defaultDiscount = new Discount('next-plan-default-discount-1');
        $defaultDiscount->setPrice(new Price(10.00));
        $defaultDiscount->setQuantity(1);
        $newPlan->addDiscount($defaultDiscount);
        $subscription->setPlan($newPlan);
        $nonDefault = new Discount('non-default', 2, new Price(2.50));
        $subscription->addDiscount($nonDefault);

        list(, $discounts) = $this->modifierMapper->toRequest($subscription, $newPlan);

        $this->assertEquals([
            'remove' => ['default-discount-1'],
            'add' => [
                [
                    'quantity' => 2,
                    'amount' => 2.50,
                    'inheritedFromId' => 'non-default',
                ],
                [
                    'quantity' => 1,
                    'amount' => 10.00,
                    'inheritedFromId' => 'next-plan-default-discount-1',
                ],
            ],
        ], $discounts);
    }
}
