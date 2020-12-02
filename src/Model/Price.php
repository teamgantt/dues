<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Exception\InvalidPriceException;

class Price extends Money
{
    /**
     * Price constructor.
     */
    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidPriceException("Prices cannot be negative. Expected price > 0, but received $amount");
        }

        parent::__construct($amount);
    }
}
