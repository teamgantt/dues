<?php

namespace TeamGantt\Dues\Model;

use DateTimeImmutable;
use TeamGantt\Dues\Contracts\Arrayable;

abstract class PaymentMethod implements Arrayable
{
    protected ?Customer $customer = null;

    protected bool $isDefault = false;

    protected ?Address $billingAddress = null;

    protected ?DateTimeImmutable $expirationDate = null;

    /**
     * @return PaymentMethod
     */
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

    /**
     * @return PaymentMethod
     */
    public function setIsDefaultPaymentMethod(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /**
     * @return PaymentMethod
     */
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

        return [
            'billingAddress' => $this->billingAddress->toArray(),
        ];
    }

    public function getExpirationDate(): ?DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(DateTimeImmutable $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    abstract public function isNew(): bool;

    abstract public function isEqualTo(?PaymentMethod $method): bool;
}
