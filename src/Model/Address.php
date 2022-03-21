<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Address\Country;
use TeamGantt\Dues\Model\Address\State;

class Address implements Arrayable
{
    protected ?State $state = null;

    protected ?string $postalCode = null;

    protected ?Country $country = null;

    protected ?string $streetAddress = null;

    public function __construct(
        ?State $state = null,
        ?string $postalCode = null,
        ?Country $country = null,
        ?string $streetAddress = null)
    {
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->country = $country;
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function toArray(): array
    {
        return array_filter([
            'postalCode' => $this->postalCode,
            'state' => isset($this->state) ? $this->state->value : null,
            'country' => isset($this->country) ? $this->country : null,
            'streetAddress' => $this->streetAddress,
        ]);
    }
}
