<?php

namespace TeamGantt\Dues\Model\Subscription;

use DateTime;
use TeamGantt\Dues\Model\Builder;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
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
    public function withStatus(Status $status): self
    {
        return $this->with('status', $status);
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
     * @param AddOn[] $addOns
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
     * @param Discount[] $discounts
     *
     * @return SubscriptionBuilder
     */
    public function withDiscounts(array $discounts): self
    {
        return $this->with('discounts', $discounts);
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

    public function build(): Subscription
    {
        ['id' => $id, 'startDate' => $start, 'price' => $price, 'status' => $status, 'customer' => $customer, 'paymentMethod' => $paymentMethod, 'plan' => $plan, 'addOns' => $addOns, 'discounts' => $discounts] = $this->data;
        $this->data = [];

        $subscription = (new Subscription($id))
            ->setStartDate($start)
            ->setPrice($price)
            ->setStatus($status)
            ->setCustomer($customer)
            ->setPaymentMethod($paymentMethod)
            ->setPlan($plan);

        $addOns = $addOns ?? [];
        foreach ($addOns as $addOn) {
            $subscription->addAddOn($addOn);
        }

        $discounts = $discounts ?? [];
        foreach ($discounts as $discount) {
            $subscription->addDiscount($discount);
        }

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
