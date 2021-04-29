<?php

namespace TeamGantt\Dues\Model\Subscription;

use DateTime;
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
    /**
     * @return SubscriptionBuilder
     */
    public function withId(string $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withStartDate(DateTime $startDate): self
    {
        return $this->with('startDate', $startDate);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withPrice(Price $price): self
    {
        return $this->with('price', $price);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withBalance(Money $balance): self
    {
        return $this->with('balance', $balance);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withStatus(Status $status): self
    {
        return $this->with('status', $status);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withDaysPastDue(int $daysPastDue): self
    {
        return $this->with('daysPastDue', $daysPastDue);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withCustomer(Customer $customer): self
    {
        return $this->with('customer', $customer);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withPaymentMethod(PaymentMethod $paymentMethod): self
    {
        return $this->with('paymentMethod', $paymentMethod);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withPlan(Plan $plan): self
    {
        return $this->with('plan', $plan);
    }

    /**
     * @param Modifier[] $addOns
     *
     * @return SubscriptionBuilder
     */
    public function withAddOns(array $addOns): self
    {
        return $this->with('addOns', $addOns);
    }

    /**
     * @return SubscriptionBuilder
     */
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
     *
     * @return SubscriptionBuilder
     */
    public function withDiscounts(array $discounts): self
    {
        return $this->with('discounts', $discounts);
    }

    /**
     * @param StatusHistory[] $statusHistory
     *
     * @return SubscriptionBuilder
     */
    public function withStatusHistory(array $statusHistory): self
    {
        return $this->with('statusHistory', $statusHistory);
    }

    /**
     * @return SubscriptionBuilder
     */
    public function withDiscount(Discount $discount): self
    {
        if (!isset($this->data['discounts'])) {
            $this->data['discounts'] = [];
        }

        $this->data['discounts'][] = $discount;

        return $this;
    }

    /**
     * @return SubscriptionBuilder
     */
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
     *
     * @return SubscriptionBuilder
     */
    protected function with(string $k, $v): self
    {
        parent::with($k, $v);

        return $this;
    }
}
