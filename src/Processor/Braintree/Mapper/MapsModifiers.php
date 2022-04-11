<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\AddOn;
use Braintree\Discount;
use TeamGantt\Dues\Model\Modifier\ModifierBuilder;
use TeamGantt\Dues\Model\Price;

trait MapsModifiers
{
    /**
     * @param AddOn|Discount $result
     */
    protected function fromGenericResult(ModifierBuilder $builder, $result): void
    {
        if (!empty($result->quantity)) {
            $builder->withQuantity($result->quantity);
        }

        $neverExpires = isset($result->neverExpires) ? $result->neverExpires : true;
        $numberOfBillingCycles = isset($result->numberOfBillingCycles) ? $result->numberOfBillingCycles : INF;
        $currentBillingCycle = isset($result->currentBillingCycle) ? $result->currentBillingCycle : 1;

        $isExpired = !$neverExpires && $currentBillingCycle >= $numberOfBillingCycles;

        $builder
            ->withId($result->id)
            ->withPrice(new Price(floatval($result->amount)))
            ->withIsExpired($isExpired);
    }
}
