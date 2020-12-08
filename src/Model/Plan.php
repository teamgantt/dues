<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Subscription\AddOn;
use TeamGantt\Dues\Model\Subscription\Discount;

class Plan extends Entity implements Arrayable
{
    protected ?Price $price = null;

    /**
     * @var AddOn[]
     */
    protected $addOns = [];

    /**
     * @var Discount[]
     */
    protected $discounts = [];

    protected int $billingFrequency = 0;

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

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @param AddOn[] $addOns
     *
     * @return Plan
     */
    public function setAddOns(array $addOns): self
    {
        $this->addOns = $addOns;

        return $this;
    }

    /**
     * @return AddOn[]
     */
    public function getAddOns(): array
    {
        return $this->addOns;
    }

    /**
     * @param Discount[] $discounts
     *
     * @return Plan
     */
    public function setDiscounts(array $discounts): self
    {
        $this->discounts = $discounts;

        return $this;
    }

    /**
     * @return Discount[]
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }
}
