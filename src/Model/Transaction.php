<?php

namespace TeamGantt\Dues\Model;

use DateTime;
use DateTimeZone;
use TeamGantt\Dues\Model\Modifier\HasModifiers;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Transaction\Status;
use TeamGantt\Dues\Model\Transaction\Type;

class Transaction extends Entity
{
    use HasModifiers;

    protected Subscription $subscription;

    protected Customer $customer;

    protected string $companyName = '';

    protected Plan $plan;

    protected Money $amount;

    protected Type $type;

    protected Status $status;

    protected DateTime $createdAt;

    public function __construct(string $id = '', Subscription $subscription)
    {
        parent::__construct($id);
        $this->subscription = $subscription;
        $this->plan = new NullPlan();
        $this->amount = new Money(0.00);
        $this->type = Type::initialized();
        $this->status = Status::initialized();
        $this->createdAt = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return Transaction
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * @return Transaction
     */
    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @return Transaction
     */
    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    /**
     * @return Transaction
     */
    public function setAmount(Money $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    /**
     * @return Transaction
     */
    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    /**
     * @return Transaction
     */
    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return Transaction
     */
    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }
}