<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Exception\InvalidPriceException;

class Price implements Arrayable
{
    protected float $amount;

    /**
     * Price constructor.
     */
    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidPriceException("Prices cannot be negative. Expected price > 0, but received $amount");
        }

        $this->amount = $amount;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount(),
        ];
    }
}
