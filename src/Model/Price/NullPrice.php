<?php

namespace TeamGantt\Dues\Model\Price;

use TeamGantt\Dues\Model\Price;

class NullPrice extends Price
{
    public function __construct()
    {
        parent::__construct(0.0);
    }

    public function getAmount(): float
    {
        return -0.01;
    }

    public function toArray(): array
    {
        return [];
    }
}
