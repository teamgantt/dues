<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Plan as BraintreePlan;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;

class PlanMapper
{
    private AddOnMapper $addOnMapper;

    private DiscountMapper $discountMapper;

    public function __construct(AddOnMapper $addOnMapper, DiscountMapper $discountMapper)
    {
        $this->addOnMapper = $addOnMapper;
        $this->discountMapper = $discountMapper;
    }

    public function fromResult(BraintreePlan $result): Plan
    {
        $plan = new Plan($result->id);
        $plan->setPrice(new Price((float) $result->price));
        $plan->setAddOns($this->addOnMapper->fromResults($result->addOns));
        $plan->setDiscounts($this->discountMapper->fromResults($result->discounts));
        $plan->setBillingFrequency($result->billingFrequency);

        return $plan;
    }
}
