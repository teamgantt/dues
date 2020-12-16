<?php

namespace TeamGantt\Dues\Model;

use DateTime;
use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription\Modifier\Operation;
use TeamGantt\Dues\Model\Subscription\Modifier\OperationType;
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

    protected ?Plan $previousPlan = null;

    protected ?Money $balance = null;

    protected Modifiers $addOns;

    protected Modifiers $discounts;

    /**
     * @var Transaction[]
     */
    protected array $transactions = [];

    public function __construct(string $id = '')
    {
        parent::__construct($id);
        $this->status = Status::initialized();
        $this->customer = new Customer();
        $this->addOns = new Modifiers();
        $this->discounts = new Modifiers();
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

        $this->setAddOns($other->getAddOnsImpl());
        $this->setDiscounts($other->getDiscountsImpl());
        $this->setTransactions($other->getTransactions());

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
        $this->setAddOns(new Modifiers());
        $this->setDiscounts(new Modifiers());

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
        $this->discounts->push($discount, OperationType::add());

        return $this;
    }

    /**
     * @internal
     */
    public function getDiscountsImpl(): Modifiers
    {
        return $this->discounts;
    }

    /**
     * @return Modifier[]
     */
    public function getDiscounts(): array
    {
        return $this->discounts
            ->filter(fn (Operation $op) => !$op->getType()->equals(OperationType::remove()))
            ->toModifierArray();
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
        $this->discounts->push(new Discount($id), OperationType::remove());

        return $this;
    }

    /**
     * @return Subscription
     */
    public function addAddOn(AddOn $addOn): self
    {
        $this->addOns->push($addOn, OperationType::add());

        return $this;
    }

    /**
     * @internal
     */
    public function getAddOnsImpl(): Modifiers
    {
        return $this->addOns;
    }

    /**
     * @return Modifier[]
     */
    public function getAddOns(): array
    {
        return $this->addOns
            ->filter(fn (Operation $op) => !$op->getType()->equals(OperationType::remove()))
            ->toModifierArray();
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
        $this->addOns->push(new AddOn($id), OperationType::remove());

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
    public function setPlan(Plan $plan): self
    {
        if ($plan->isEqualTo($this->plan)) {
            $this->plan = $plan; // allow updating to a newer instance of the same plan

            return $this;
        }

        $this->setPriceFromPlan($plan);
        $this->previousPlan = $this->plan;
        $this->plan = $plan;

        return $this;
    }

    public function getPreviousPlan(): ?Plan
    {
        if ($this->hasChangedPlans()) {
            return $this->previousPlan;
        }

        return null;
    }

    public function hasChangedPlans(): bool
    {
        if (null === $this->previousPlan) {
            return false;
        }

        return !$this->previousPlan instanceof NullPlan;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Set the value of transactions.
     *
     * @param Transaction[] $transactions
     *
     * @return Subscription
     */
    public function setTransactions(array $transactions): self
    {
        $this->transactions = $transactions;

        return $this;
    }

    public function addTransaction(Transaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    private function setPriceFromPlan(Plan $plan): void
    {
        $plan->getPrice()->applyToSubscription($this);
    }
}
