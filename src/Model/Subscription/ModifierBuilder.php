<?php

namespace TeamGantt\Dues\Model\Subscription;

use TeamGantt\Dues\Model\Builder;
use TeamGantt\Dues\Model\Price;

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

    protected function buildModifier(Modifier $modifier): void
    {
        if (!empty($this->get('quantity'))) {
            $modifier->setQuantity($this->get('quantity'));
        }

        $modifier->setPrice($this->get('price'));

        $this->reset();
    }

    /**
     * @param mixed $v
     *
     * @return ModifierBuilder
     */
    protected function with(string $k, $v): self
    {
        parent::with($k, $v);

        return $this;
    }
}
