<?php

namespace TeamGantt\Dues\Model\PaymentMethod;

use TeamGantt\Dues\Model\PaymentMethod;

class Nonce extends PaymentMethod
{
    private string $nonce;

    public function __construct(string $nonce)
    {
        $this->nonce = $nonce;
    }

    public function getValue(): string
    {
        return $this->nonce;
    }

    public function toArray(): array
    {
        $customer = $this->getCustomer();

        return [
            'customerId' => empty($customer) ? null : $customer->getId(),
            'nonce' => $this->nonce,
        ];
    }

    /**
     * A nonce always represents a new, unrealized payment method.
     */
    public function isNew(): bool
    {
        return true;
    }

    public function isEqualTo(?PaymentMethod $method): bool
    {
        if (!$method instanceof Nonce) {
            return false;
        }

        return $method->getValue() === $this->getValue();
    }
}
