<?php

namespace TeamGantt\Dues\Model;

use DateTime;
use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription\AddOn;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Model\Subscription\Modifier;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\Status;

class Subscription extends Entity implements Arrayable
{
    protected ?DateTime $startDate = null;

    protected ?Price $price = null;

    protected Status $status;

    protected Customer $customer;

    protected ?PaymentMethod $paymentMethod = null;

    protected Plan $plan;

    protected ?Money $balance = null;

    protected Modifiers $addOns;

    protected Modifiers $discounts;

    public function __construct(string $id = '')
    {
        parent::__construct($id);
        $this->status = Status::initialized();
        $this->customer = new Customer();
        $this->addOns = new Modifiers($this);
        $this->discounts = new Modifiers($this);
        $this->plan = new NullPlan();
    }

    public function toArray(): array
    {
        $price = $this->getPrice();
        $payment = $this->getPaymentMethod();

        return array_filter([
            'id' => $this->getId(),
            'startDate' => $this->getStartDate(),
            'price' => empty($price) ? null : $price->toArray(),
            'status' => $this->getStatus(),
            'customer' => $this->getCustomer()->toArray(),
            'payment' => empty($payment) ? null : $payment->toArray(),
            'plan' => $this->plan->toArray(),
            'addOns' => $this->addOns->toArray(),
            'discounts' => $this->discounts->toArray(),
        ]);
    }

    /**
     * Merge another Subscription into this one. Preserves the entity ID.
     *
     * @internal
     */
    public function merge(Subscription $other): Subscription
    {
        if ($startDate = $other->getStartDate()) {
            $this->setStartDate($startDate);
        }

        $price = $other->getPrice();
        if ($price && !$price instanceof NullPrice) {
            $this->setPrice($price);
        }

        $this->setStatus($other->getStatus());

        $this->setCustomer($other->getCustomer());

        if ($payment = $other->getPaymentMethod()) {
            $this->setPaymentMethod($payment);
        }

        $plan = $other->getPlan();
        if (!$plan instanceof NullPlan) {
            $this->setPlan($plan);
        }

        if ($balance = $other->getBalance()) {
            $this->setBalance($balance);
        }

        $this->setAddOns($other->getAddOns());
        $this->setDiscounts($other->getDiscounts());

        return $this;
    }

    /**
     * Closing a subscription out sets plan, price, and modifier
     * totals to zero values.
     *
     * @return Subscription
     */
    public function closeOut(): self
    {
        $this->setPlan(new NullPlan());
        $this->setPrice(new Price(0.0));
        $this->setAddOns(new Modifiers($this));
        $this->setDiscounts(new Modifiers($this));

        return $this;
    }

    public function getBalance(): ?Money
    {
        return $this->balance;
    }

    /**
     * @return Subscription
     */
    public function setBalance(Money $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return Subscription
     */
    public function addDiscount(Discount $discount): self
    {
        $this->discounts->add($discount);

        return $this;
    }

    public function getDiscounts(): Modifiers
    {
        return $this->discounts;
    }

    /**
     * @return Subscription
     */
    public function setDiscounts(Modifiers $discounts): self
    {
        $this->discounts = $discounts;

        return $this;
    }

    /**
     * @return Subscription
     */
    public function removeDiscount(string $id): self
    {
        $this->discounts->remove($id);

        return $this;
    }

    /**
     * @return Subscription
     */
    public function addAddOn(AddOn $addOn): self
    {
        $this->addOns->add($addOn);

        return $this;
    }

    public function getAddOns(): Modifiers
    {
        return $this->addOns;
    }

    /**
     * @return Subscription
     */
    public function setAddOns(Modifiers $addOns): self
    {
        $this->addOns = $addOns;

        return $this;
    }

    /**
     * @return Subscription
     */
    public function removeAddOn(string $id): self
    {
        $this->addOns->remove($id);

        return $this;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * @return Subscription
     */
    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return Subscription
     */
    public function beginImmediately()
    {
        $this->startDate = null;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * @return Subscription
     */
    public function setPrice(Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function is(Status $status): bool
    {
        $current = $this->getStatus();

        return $status->equals($current);
    }

    /**
     * @param Status $statuses,...
     */
    public function isNot(...$statuses): bool
    {
        foreach ($statuses as $status) {
            if ($this->is($status)) {
                return false;
            }
        }

        return true;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * @return Subscription
     */
    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Subscription
     */
    public function cancel(): self
    {
        return $this->setStatus(Status::canceled());
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return Subscription
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        if (isset($this->paymentMethod)) {
            return $this->paymentMethod;
        }

        if (isset($this->customer)) {
            return $this->customer->getDefaultPaymentMethod();
        }

        return null;
    }

    /**
     * @return Subscription
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    /**
     * @return Subscription
     */
    public function resetPlan(Plan $plan): self
    {
        return $this
            ->setPlan(new NullPlan())
            ->setPlan($plan);
    }

    /**
     * @return Subscription
     */
    public function setPlan(Plan $plan): self
    {
        if (!$plan->isEqualTo($this->plan)) {
            $this->setPrice($plan->getPrice());
        }

        $this->mergePlanDefaults($plan);

        $this->plan = $plan;

        return $this;
    }

    private function mergePlanDefaults(Plan $plan): void
    {
        $this->setAddOns($this->mergeCurrentModifiers($this->getAddOns(), $plan->getAddOns()));
        $this->setDiscounts($this->mergeCurrentModifiers($this->getDiscounts(), $plan->getDiscounts()));
    }

    /**
     * @param Modifier[] $modifiers
     */
    private function mergeCurrentModifiers(Modifiers $source, array $modifiers): Modifiers
    {
        if (empty($modifiers)) {
            return $source;
        }

        $provided = $source->getAll();
        $modifierDefaults = new Modifiers($this, $modifiers);

        foreach ($provided as $modifier) {
            $default = $modifierDefaults->get($modifier->getId());

            if (null === $default || $default->isEqualTo($modifier)) {
                continue;
            }

            $modifierDefaults->add($modifier);
        }

        return $modifierDefaults;
    }
}
