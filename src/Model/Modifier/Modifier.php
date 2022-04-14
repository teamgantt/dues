<?php

namespace TeamGantt\Dues\Model\Modifier;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Contracts\Valuable;
use TeamGantt\Dues\Model\Entity;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;

abstract class Modifier extends Entity implements Arrayable, Valuable
{
    protected Price $price;

    protected ?int $quantity = null;

    protected bool $isExpired = false;

    public function __construct(string $id = '', ?int $quantity = null, ?Price $price = null)
    {
        parent::__construct($id);
        $this->quantity = $quantity;
        $this->price = $price ?? new NullPrice();
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function isExpired(): bool
    {
        return $this->isExpired;
    }

    public function setIsExpired(bool $isExpired): Modifier
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    public function setPrice(Price $price): Modifier
    {
        $this->price = $price;

        return $this;
    }

    public function getValue(): Money
    {
        if (null === $this->quantity) {
            return new Money(0.0);
        }

        return new Money($this->price->getAmount() * $this->quantity);
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

    public function isEqualTo(?Entity $other): bool
    {
        if (null === $other) {
            return false;
        }

        if (!$other instanceof Modifier) {
            return false;
        }
        $idsAreEqual = $this->getId() === $other->getId();
        $quantitiesAreEqual = $this->getQuantity() === $other->getQuantity();
        $pricesAreEqual = $this->getPrice() == $other->getPrice();

        return $idsAreEqual && $quantitiesAreEqual && $pricesAreEqual;
    }
}
