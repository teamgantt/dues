<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;

abstract class PaymentMethod implements Arrayable
{
    protected ?Customer $customer = null;

    protected bool $isDefault = false;

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

    abstract public function isNew(): bool;

    abstract public function isEqualTo(?PaymentMethod $method): bool;
}
