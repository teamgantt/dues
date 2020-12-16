<?php

namespace TeamGantt\Dues\Tests\Feature;

trait Plan
{
    /**
     * @group integration
     *
     * @return void
     */
    public function testListPlans()
    {
        $plans = $this->gateway->listPlans();
        $this->assertNotEmpty($plans);
    }

    /**
     * @group integration
     *
     * @return void
     */
    public function testFindPlanById()
    {
        $plan = $this->gateway->findPlanById('test-plan-c-yearly');

        $this->assertEquals('test-plan-c-yearly', $plan->getId());
        $this->assertNotNull($plan->getPrice());
    }

    /**
     * @group integration
     *
     * @return void
     */
    public function testFindPlanWithModifiersById()
    {
        $plan = $this->gateway->findPlanById('test-plan-d-yearly');
        $addOns = $plan->getAddOns();
        $discounts = $plan->getDiscounts();

        $this->assertEquals('test-plan-d-yearly', $plan->getId());
        $this->assertEquals(12, $plan->getBillingFrequency());
        $this->assertNotNull($plan->getPrice());
        $this->assertEquals('test-plan-d-yearly-u', current($addOns)->getId());
        $this->assertEquals('test-plan-d-yearly-discount', current($discounts)->getId());
    }
}
