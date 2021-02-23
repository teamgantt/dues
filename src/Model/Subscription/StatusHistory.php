<?php

namespace TeamGantt\Dues\Model\Subscription;

use DateTime;
use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Money;

class StatusHistory implements Arrayable
{
    public DateTime $timestamp;

    public Status $status;

    public Money $balance;

    public Money $price;

    public string $planId;

    public function __construct(DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setBalance(Money $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function setPrice(Money $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setPlanId(string $planId): self
    {
        $this->planId = $planId;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function toArray(): array
    {
        return array_filter([
            'timestamp' => $this->timestamp,
            'status' => isset($this->status) ? $this->status : null,
            'balance' => isset($this->balance) ? $this->balance->getAmount() : null,
            'price' => isset($this->price) ? $this->price->getAmount() : null,
            'planId' => isset($this->planId) ? $this->planId : null,
        ]);
    }
}
