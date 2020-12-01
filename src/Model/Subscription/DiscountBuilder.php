<?php

namespace TeamGantt\Dues\Model\Subscription;

class DiscountBuilder extends ModifierBuilder
{
    public function build(): Discount
    {
        $discount = new Discount($this->getId());
        $this->buildModifier($discount);

        return $discount;
    }
}
