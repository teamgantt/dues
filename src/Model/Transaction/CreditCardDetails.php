<?php

namespace TeamGantt\Dues\Model\Transaction;

use TeamGantt\Dues\Model\CreditCardType;

class CreditCardDetails
{
    protected CreditCardType $cardType;

    protected string $lastFour;

    public function __construct(CreditCardType $cardType, string $lastFour)
    {
        $this->cardType = $cardType;
        $this->lastFour = $lastFour;
    }

    public function getCardType(): CreditCardType
    {
        return $this->cardType;
    }

    /**
     * @return CreditCardDetails
     */
    public function setCardType(CreditCardType $cardType): self
    {
        $this->cardType = $cardType;

        return $this;
    }

    public function getLastFour(): string
    {
        return $this->lastFour;
    }

    /**
     * @return CreditCardDetails
     */
    public function setLastFour(string $lastFour): self
    {
        $this->lastFour = $lastFour;

        return $this;
    }
}
