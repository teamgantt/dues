<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Modifier\HasModifiers;
use TeamGantt\Dues\Model\Price\NullPrice;

class Plan extends Entity implements Arrayable
{
    use HasModifiers;

    protected Price $price;

    protected int $billingFrequency = 0;

    public function __construct(string $id = '')
    {
        parent::__construct($id);
        $this->price = new NullPrice();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
        ];
    }

    public function getBillingFrequency(): int
    {
        return $this->billingFrequency;
    }

    /**
     * @return Plan
     */
    public function setBillingFrequency(int $frequency): self
    {
        $this->billingFrequency = $frequency;

        return $this;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function setPrice(Price $price): self
    {
        $this->price = $price;

        return $this;
    }
}
