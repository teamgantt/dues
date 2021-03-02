<?php

namespace TeamGantt\Dues\Model\PaymentMethod;

use DomainException;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;

class NullPaymentMethod extends PaymentMethod
{
    public function toArray(): array
    {
        return [];
    }

    public function setCustomer(Customer $customer): self
    {
        throw new DomainException('Not implemented.');
    }

    public function setIsDefaultPaymentMethod(bool $isDefault): self
    {
        throw new DomainException('Not implemented.');
    }

    public function setBillingAddress(Address $address): self
    {
        throw new DomainException('Not implemented.');
    }

    public function isNew(): bool
    {
        return false;
    }

    public function isEqualTo(?PaymentMethod $method): bool
    {
        return false;
    }
}
