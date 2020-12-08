<?php

namespace TeamGantt\Dues\Model\Plan;

use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;

class NullPlan extends Plan
{
    public function toArray(): array
    {
        return [];
    }

    public function getPrice(): Price
    {
        return new NullPrice();
    }

    public function getAddOns(): array
    {
        return [];
    }

    public function getDiscounts(): array
    {
        return [];
    }
}
