<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;

class Customer extends Entity implements Arrayable
{
    protected string $firstName = '';

    protected string $lastName = '';

    protected string $emailAddress = '';

    /**
     * @var PaymentMethod[]
     */
    protected array $paymentMethods = [];

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function addPaymentMethod(PaymentMethod $paymentMethod): self
    {
        if ($this->hasPaymentMethod($paymentMethod)) {
            return $this;
        }

        $paymentMethod->setCustomer($this);

        if ($paymentMethod->isDefaultPaymentMethod()) {
            $this->clearDefaultPaymentMethod();
        }

        $this->paymentMethods[] = $paymentMethod;

        return $this;
    }

    public function clearDefaultPaymentMethod(): void
    {
        $this->paymentMethods = array_reduce($this->paymentMethods, function (array $accum, PaymentMethod $method) {
            if ($method->isDefaultPaymentMethod()) {
                $method->setIsDefaultPaymentMethod(false);
            }

            return [...$accum, $method];
        }, []);
    }

    public function hasPaymentMethod(PaymentMethod $paymentMethod): bool
    {
        foreach ($this->paymentMethods as $method) {
            if ($method->isEqualTo($paymentMethod)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PaymentMethod[] $paymentMethods
     */
    public function setPaymentMethods(array $paymentMethods): self
    {
        $this->paymentMethods = [];

        foreach ($paymentMethods as $method) {
            $this->addPaymentMethod($method);
        }

        return $this;
    }

    /**
     * @return PaymentMethod[]
     */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    public function getDefaultPaymentMethod(): ?PaymentMethod
    {
        $paymentMethods = $this->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->isDefaultPaymentMethod()) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->getId(),
            'emailAddress' => $this->getEmailAddress(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
        ]);
    }
}
