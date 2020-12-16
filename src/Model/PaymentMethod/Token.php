<?php

namespace TeamGantt\Dues\Model\PaymentMethod;

use TeamGantt\Dues\Model\PaymentMethod;

class Token extends PaymentMethod
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getValue(): string
    {
        return $this->token;
    }

    public function toArray(): array
    {
        return ['token' => $this->token];
    }

    /**
     * A token always represents an established, realized payment
     * method.
     */
    public function isNew(): bool
    {
        return false;
    }

    public function isEqualTo(?PaymentMethod $method): bool
    {
        if (!$method instanceof Token) {
            return false;
        }

        return $method->getValue() === $this->getValue();
    }
}
