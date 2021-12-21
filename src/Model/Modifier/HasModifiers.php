<?php

namespace TeamGantt\Dues\Model\Modifier;

trait HasModifiers
{
    /**
     * @var array<string, AddOn>
     */
    protected $addOns = [];

    /**
     * @var array<string, Discount>
     */
    protected $discounts = [];

    /**
     * @param AddOn[] $addOns
     */
    public function setAddOns(array $addOns): self
    {
        $this->addOns = [];

        foreach ($addOns as $addOn) {
            $this->addAddOn($addOn);
        }

        return $this;
    }

    public function addAddOn(AddOn $addOn): self
    {
        $this->addOns[$addOn->getId()] = $addOn;

        return $this;
    }

    /**
     * @return array<string, AddOn>
     */
    public function getAddOns(): array
    {
        return $this->addOns;
    }

    public function getAddOn(string $id): ?AddOn
    {
        return $this->addOns[$id] ?? null;
    }

    /**
     * @param Discount[] $discounts
     */
    public function setDiscounts(array $discounts): self
    {
        $this->discounts = [];

        foreach ($discounts as $discount) {
            $this->addDiscount($discount);
        }

        return $this;
    }

    public function addDiscount(Discount $discount): self
    {
        $this->discounts[$discount->getId()] = $discount;

        return $this;
    }

    /**
     * @return array<string, Discount>
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getDiscount(string $id): ?Discount
    {
        return $this->discounts[$id] ?? null;
    }

    public function getModifier(string $id): ?Modifier
    {
        if ($addOn = $this->getAddOn($id)) {
            return $addOn;
        }

        return $this->getDiscount($id);
    }

    public function hasModifiers(): bool
    {
        return !empty($this->addOns) || !empty($this->discounts);
    }

    public function hasAddOn(string $id): bool
    {
        return isset($this->getAddOns()[$id]);
    }

    public function hasDiscount(string $id): bool
    {
        return isset($this->getDiscounts()[$id]);
    }
}
