<?php

namespace TeamGantt\Dues\Model\Subscription;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Entity;
use TeamGantt\Dues\Model\Price;

abstract class Modifier extends Entity implements Arrayable
{
    protected ?Price $price = null;

    protected ?int $quantity = null;

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): Modifier
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): Modifier
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function toArray(): array
    {
        $array = array_filter([
            'id' => $this->getId(),
            'quantity' => $this->getQuantity(),
        ]);

        if ($price = $this->getPrice()) {
            return array_merge($array, $price->toArray());
        }

        return $array;
    }
}
