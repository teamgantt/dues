<?php

namespace TeamGantt\Dues\Model\Customer;

use TeamGantt\Dues\Model\Builder;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;

class CustomerBuilder extends Builder
{
    public function withId(string $id): self
    {
        return $this->with('id', $id);
    }

    public function withFirstName(string $firstName): self
    {
        return $this->with('firstName', $firstName);
    }

    public function withLastName(string $lastName): self
    {
        return $this->with('lastName', $lastName);
    }

    public function withEmailAddress(string $address): self
    {
        return $this->with('emailAddress', $address);
    }

    /**
     * Set internal builder state to match that of
     * the given customer.
     */
    public function fromCustomer(Customer $customer): self
    {
        $this->data = [
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'emailAddress' => $customer->getEmailAddress(),
            'paymentMethods' => $customer->getPaymentMethods(),
        ];

        return $this;
    }

    /**
     * Include a PaymentMethod. This method can be called
     * multiple times to add additional payment methods.
     */
    public function withPaymentMethod(PaymentMethod $paymentMethod): self
    {
        if (!isset($this->data['paymentMethods'])) {
            $this->data['paymentMethods'] = [];
        }

        $this->data['paymentMethods'][] = $paymentMethod;

        return $this;
    }

    public function build(): Customer
    {
        $customer = (new Customer($this->getId()))
            ->setFirstName((string) $this->data['firstName'])
            ->setLastName((string) $this->data['lastName'])
            ->setEmailAddress((string) $this->data['emailAddress']);

        $paymentMethods = $this->data['paymentMethods'] ?? [];

        foreach ($paymentMethods as $paymentMethod) {
            $customer->addPaymentMethod($paymentMethod);
        }

        $this->reset();

        return $customer;
    }

    /**
     * @param mixed $v
     *
     * @return CustomerBuilder
     */
    protected function with(string $k, $v): self
    {
        parent::with($k, $v);

        return $this;
    }
}
