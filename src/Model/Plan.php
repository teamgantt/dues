<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;

class Plan extends Entity implements Arrayable
{
    protected ?Price $price = null;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
        ];
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
}
