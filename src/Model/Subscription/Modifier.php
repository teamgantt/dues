<?php

namespace TeamGantt\Dues\Model\Subscription;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Entity;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;

abstract class Modifier extends Entity implements Arrayable
{
    protected Price $price;

    protected ?int $quantity = null;

    public function __construct(?string $id = null, ?int $quantity = null, ?Price $price = null)
    {
        parent::__construct($id);
        $this->quantity = $quantity;
        $this->price = $price ?? new NullPrice();
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(Price $price): Modifier
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): Modifier
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

    public function isEqualTo(Entity $other): bool
    {
        if (!$other instanceof Modifier) {
            return false;
        }
        $idsAreEqual = $this->getId() === $other->getId();
        $quantitiesAreEqual = $this->getQuantity() === $other->getQuantity();
        $pricesAreEqual = $this->getPrice() == $other->getPrice();

        return $idsAreEqual && $quantitiesAreEqual && $pricesAreEqual;
    }
}
