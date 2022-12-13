<?php

namespace TeamGantt\Dues\Model\Subscription;

use TeamGantt\Dues\Model\Builder;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription;

class SubscriptionBuilder extends Builder
{
    public function withId(string $id): self
    {
        return $this->with('id', $id);
    }

    public function withStartDate(\DateTime $startDate): self
    {
        return $this->with('startDate', $startDate);
    }

    public function withPrice(Price $price): self
    {
        return $this->with('price', $price);
    }

    public function withBalance(Money $balance): self
    {
        return $this->with('balance', $balance);
    }

    public function withStatus(Status $status): self
    {
        return $this->with('status', $status);
    }

    public function withDaysPastDue(int $daysPastDue): self
    {
        return $this->with('daysPastDue', $daysPastDue);
    }

    public function withCustomer(Customer $customer): self
    {
        return $this->with('customer', $customer);
    }

    public function withPaymentMethod(PaymentMethod $paymentMethod): self
    {
        return $this->with('paymentMethod', $paymentMethod);
    }

    public function withPlan(Plan $plan): self
    {
        return $this->with('plan', $plan);
    }

    public function withBillingPeriod(?\DateTime $start, ?\DateTime $end): self
    {
        if (isset($start, $end)) {
            return $this->with('billingPeriod', new BillingPeriod($start, $end));
        }

        return $this;
    }

    /**
     * @param Modifier[] $addOns
     */
    public function withAddOns(array $addOns): self
    {
        return $this->with('addOns', $addOns);
    }

    public function withAddOn(AddOn $addOn): self
    {
        if (!isset($this->data['addOns'])) {
            $this->data['addOns'] = [];
        }

        $this->data['addOns'][] = $addOn;

        return $this;
    }

    /**
     * @param Modifier[] $discounts
     */
    public function withDiscounts(array $discounts): self
    {
        return $this->with('discounts', $discounts);
    }

    /**
     * @param StatusHistory[] $statusHistory
     */
    public function withStatusHistory(array $statusHistory): self
    {
        return $this->with('statusHistory', $statusHistory);
    }

    public function withDiscount(Discount $discount): self
    {
        if (!isset($this->data['discounts'])) {
            $this->data['discounts'] = [];
        }

        $this->data['discounts'][] = $discount;

        return $this;
    }

    public function withNextBillingPeriodAmount(Money $amount): self
    {
        return $this->with('nextBillingPeriodAmount', $amount);
    }

    public function build(): Subscription
    {
        $subscription = (new Subscription($this->getId()))
            ->setPrice($this->data['price'] ?? new NullPrice())
            ->setStatus($this->data['status'] ?? Status::initialized())
            ->setDaysPastDue($this->data['daysPastDue'] ?? 0)
            ->setCustomer($this->data['customer'] ?? new Customer());

        if (isset($this->data['startDate'])) {
            $subscription->setStartDate($this->data['startDate']);
        }

        if (isset($this->data['paymentMethod'])) {
            $subscription->setPaymentMethod($this->data['paymentMethod']);
        }

        if (isset($this->data['balance'])) {
            $subscription->setBalance($this->data['balance']);
        }

        if (isset($this->data['nextBillingPeriodAmount'])) {
            $subscription->setNextBillingPeriodAmount($this->data['nextBillingPeriodAmount']);
        }

        if (isset($this->data['billingPeriod'])) {
            $subscription->setBillingPeriod($this->data['billingPeriod']);
        }

        $addOns = $this->data['addOns'] ?? [];
        foreach ($addOns as $addOn) {
            $subscription->addAddOn($addOn);
        }

        $discounts = $this->data['discounts'] ?? [];
        foreach ($discounts as $discount) {
            $subscription->addDiscount($discount);
        }

        $subscription->setPlan($this->data['plan'] ?? new NullPlan());

        $subscription->setStatusHistory($this->data['statusHistory'] ?? []);

        $this->reset();

        return $subscription;
    }

    /**
     * @param mixed $v
     */
    protected function with(string $k, $v): self
    {
        parent::with($k, $v);

        return $this;
    }
}
