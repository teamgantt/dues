<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Address\State;

class Address implements Arrayable
{
    protected ?State $state = null;

    protected ?string $postalCode = null;

    public function __construct(?State $state = null, ?string $postalCode = null)
    {
        $this->state = $state;
        $this->postalCode = $postalCode;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function toArray(): array
    {
        return array_filter([
            'postalCode' => $this->postalCode,
            'state' => isset($this->state) ? $this->state->value : null,
        ]);
    }
}
