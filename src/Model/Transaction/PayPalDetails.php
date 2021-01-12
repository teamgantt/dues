<?php

namespace TeamGantt\Dues\Model\Transaction;

class PayPalDetails
{
    protected string $payerEmail;

    public function __construct(string $payerEmail)
    {
        $this->payerEmail = $payerEmail;
    }

    public function getPayerEmail(): string
    {
        return $this->payerEmail;
    }

    /**
     * @return PayPalDetails
     */
    public function setPayerEmail(string $payerEmail): self
    {
        $this->payerEmail = $payerEmail;

        return $this;
    }
}
