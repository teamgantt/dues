<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Model\Modifier\HasModifiers;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Transaction\CreditCardDetails;
use TeamGantt\Dues\Model\Transaction\PaymentInstrumentType;
use TeamGantt\Dues\Model\Transaction\PayPalDetails;
use TeamGantt\Dues\Model\Transaction\Status;
use TeamGantt\Dues\Model\Transaction\Type;

class Transaction extends Entity
{
    use HasModifiers;

    protected ?Subscription $subscription;

    protected Customer $customer;

    protected string $companyName = '';

    protected Plan $plan;

    protected Money $amount;

    protected Type $type;

    protected Status $status;

    protected PaymentInstrumentType $paymentInstrumentType;

    protected \DateTime $createdAt;

    protected ?CreditCardDetails $creditCardDetails = null;

    protected ?PayPalDetails $payPalDetails = null;

    protected ?Address $billingDetails = null;

    public function __construct(string $id = '', ?Subscription $subscription = null)
    {
        parent::__construct($id);
        $this->subscription = $subscription;
        $this->plan = new NullPlan();
        $this->amount = new Money(0.00);
        $this->type = Type::initialized();
        $this->status = Status::initialized();
        $this->paymentInstrumentType = PaymentInstrumentType::initialized();
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getBillingDetails(): ?Address
    {
        return $this->billingDetails;
    }

    public function setBillingDetails(Address $details): self
    {
        $this->billingDetails = $details;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPaymentInstrumentType(): PaymentInstrumentType
    {
        return $this->paymentInstrumentType;
    }

    public function setPaymentInstrumentType(PaymentInstrumentType $paymentInstrumentType): self
    {
        $this->paymentInstrumentType = $paymentInstrumentType;

        return $this;
    }

    public function getCreditCardDetails(): ?CreditCardDetails
    {
        return $this->creditCardDetails;
    }

    public function setCreditCardDetails(CreditCardDetails $creditCardDetails): self
    {
        $this->creditCardDetails = $creditCardDetails;

        return $this;
    }

    public function getPayPalDetails(): ?PayPalDetails
    {
        return $this->payPalDetails;
    }

    public function setPayPalDetails(PayPalDetails $payPalDetails): self
    {
        $this->payPalDetails = $payPalDetails;

        return $this;
    }
}
