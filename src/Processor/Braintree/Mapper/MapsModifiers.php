<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\AddOn;
use Braintree\Discount;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription\ModifierBuilder;

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

        $builder
            ->withId($result->id)
            ->withPrice(new Price(floatval($result->amount)));
    }
}
