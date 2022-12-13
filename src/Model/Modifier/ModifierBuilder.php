<?php

namespace TeamGantt\Dues\Model\Modifier;

use TeamGantt\Dues\Model\Builder;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;

abstract class ModifierBuilder extends Builder
{
    public function withId(string $id): self
    {
        return $this->with('id', $id);
    }

    public function withQuantity(int $quantity): self
    {
        return $this->with('quantity', $quantity);
    }

    public function withPrice(Price $price): self
    {
        return $this->with('price', $price);
    }

    public function withIsExpired(bool $isExpired): self
    {
        return $this->with('isExpired', $isExpired);
    }

    public function withNumberOfBillingCycles(float $numberOfBillingCycles): self
    {
        return $this->with('numberOfBillingCycles', $numberOfBillingCycles);
    }

    protected function buildModifier(Modifier $modifier): void
    {
        if (isset($this->data['quantity'])) {
            $modifier->setQuantity((int) $this->data['quantity']);
        }

        $modifier->setPrice($this->data['price'] ?? new NullPrice());
        $modifier->setIsExpired($this->data['isExpired'] ?? false);

        if (isset($this->data['numberOfBillingCycles'])) {
            $modifier->setNumberOfBillingCycles($this->data['numberOfBillingCycles']);
        }

        $this->reset();
    }

    /**
     * @param mixed $v
     */
    protected function with(string $k, $v): self
    {
        parent::with($k, $v);

        return $this;
    }
}
