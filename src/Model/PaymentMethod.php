<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;

abstract class PaymentMethod implements Arrayable
{
    protected ?Customer $customer = null;

    protected bool $isDefault = false;

    protected ?Address $billingAddress = null;

    protected ?\DateTimeImmutable $expirationDate = null;

    protected ?string $last4 = null;

    protected ?string $name = null;

    protected ?string $type = null;

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function isDefaultPaymentMethod(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefaultPaymentMethod(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(Address $address): self
    {
        $this->billingAddress = $address;

        return $this;
    }

    public function toArray(): array
    {
        if (!isset($this->billingAddress)) {
            return [];
        }

        // If the last4, name, and type are added to this, then they
        // will need to be stripped off prior to it being submitted to
        // Braintree as part of a subscription in PaymentMethodMapper::toRequest.
        return [
            'billingAddress' => $this->billingAddress->toArray(),
        ];
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeImmutable $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getLast4(): string|null
    {
        return $this->last4;
    }

    public function setLast4(string $last4): self
    {
        $this->last4 = $last4;

        return $this;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string|null
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = strtolower($type);

        return $this;
    }

    abstract public function isNew(): bool;

    abstract public function isEqualTo(?PaymentMethod $method): bool;
}
