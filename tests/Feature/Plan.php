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
}
