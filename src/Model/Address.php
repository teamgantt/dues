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

    public function __construct(?State $state = null, ?string $postalCode = null, ?Country $country = null)
    {
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->country = $country;
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

    public function toArray(): array
    {
        return array_filter([
            'postalCode' => $this->postalCode,
            'state' => isset($this->state) ? $this->state->value : null,
            'country' => isset($this->country) ? $this->country : null,
        ]);
    }
}
