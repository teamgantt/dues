<?php

namespace TeamGantt\Dues\Model;

use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Contracts\Valuable;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription\BillingPeriod;
use TeamGantt\Dues\Model\Subscription\Modifier\Operation;
use TeamGantt\Dues\Model\Subscription\Modifier\OperationType;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\StatusHistory;
use TeamGantt\Dues\Model\Subscription\Trial\Trial;

class Subscription extends Entity implements Arrayable, Valuable
{
    protected ?BillingPeriod $billingPeriod = null;

    protected ?\DateTime $startDate = null;

    protected ?Price $price = null;

    protected Status $status;

    protected int $daysPastDue = 0;

    protected Customer $customer;

    protected ?PaymentMethod $paymentMethod = null;

    protected Plan $plan;

    protected ?Plan $previousPlan = null;

    protected ?Money $balance = null;

    protected Modifiers $initialAddOns;

    protected Modifiers $addOns;

    protected Modifiers $initialDiscounts;

    protected Modifiers $discounts;

    protected ?Money $nextBillingPeriodAmount;

    protected ?\DateTimeInterface $nextBillingDate;

    protected bool $isProrated = true;

    protected Trial $trial;

    /**
     * @var StatusHistory[]
     */
    protected array $statusHistory = [];

    /**
     * @var Transaction[]
     */
    protected array $transactions = [];

    protected Money $remainingValue;

    public function __construct(string $id = '')
    {
        parent::__construct($id);
        $this->status = Status::initialized();
        $this->customer = new Customer();
        $this->addOns = new Modifiers();
        $this->discounts = new Modifiers();
        $this->plan = new NullPlan();
        $this->remainingValue = new Money(0.0);
    }

    public function toArray(): array
    {
        $price = $this->getPrice();
        $payment = $this->getPaymentMethod();

        return array_merge(
            array_filter([
                'id' => $this->getId(),
                'startDate' => $this->getStartDate(),
                'trial' => $this->getTrial()?->toArray(),
                'price' => empty($price) ? null : $price->toArray(),
                'status' => $this->getStatus(),
                'statusHistory' => $this->getStatusHistory(),
                'daysPastDue' => $this->getDaysPastDue(),
                'customer' => $this->getCustomer()->toArray(),
                'payment' => empty($payment) ? null : $payment->toArray(),
                'plan' => $this->plan->toArray(),
                'nextBillingPeriodAmount' => $this->getNextBillingPeriodAmount(),
                'nextBillingDate' => $this->getNextBillingDate(),
            ]),
            [
                'isProrated' => $this->isProrated(),
            ]
        );
    }

    public function getRemainingValue(): Money
    {
        return $this->remainingValue;
    }

    public function setRemainingValue(Money $value): self
    {
        $this->remainingValue = $value;

        return $this;
    }

    public function setBillingPeriod(BillingPeriod $period): self
    {
        $this->billingPeriod = $period;

        return $this;
    }

    public function getBillingPeriod(): ?BillingPeriod
    {
        return $this->billingPeriod;
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
        $this->setStatusHistory($other->getStatusHistory());

        $this->setDaysPastDue($other->getDaysPastDue());

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

    public function getValue(): Money
    {
        $price = $this->price;

        if (null === $price) {
            $price = new Money(0.0);
        }

        $priceReducer = function (float $value, Modifier $mod) {
            if (true === $mod->isExpired()) {
                return $value;
            }

            return $value + $mod->getValue()->getAmount();
        };

        $priceValue = $price->getAmount();

        $addOnValue = array_reduce($this->addOns->toModifierArray(), $priceReducer, 0.0);
        $discountValue = array_reduce($this->discounts->toModifierArray(), $priceReducer, 0.0);

        return new Money(round($priceValue + $addOnValue - $discountValue, 2));
    }

    public function setBalance(Money $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

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
     * Returns Discounts as they were when the Subscription was fetched from the Processor.
     *
     * @return Modifier[]
     */
    public function getInitialDiscounts(): array
    {
        if (!isset($this->initialDiscounts)) {
            return [];
        }

        return $this->toModifierArray($this->initialDiscounts);
    }

    /**
     * @return Modifier[]
     */
    public function getDiscounts(): array
    {
        return $this->toModifierArray($this->discounts);
    }

    public function setDiscounts(Modifiers $discounts): self
    {
        $this->discounts = $discounts;

        return $this;
    }

    public function setInitialDiscounts(Modifiers $discounts): self
    {
        $this->initialDiscounts = $discounts;

        return $this;
    }

    public function removeDiscount(string $id): self
    {
        $this->discounts->push(new Discount($id), OperationType::remove());

        return $this;
    }

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
        return $this->toModifierArray($this->addOns);
    }

    /**
     * Returns AddOns as they were when the Subscription was fetched from the Processor.
     *
     * @return Modifier[]
     */
    public function getInitialAddOns(): array
    {
        if (!isset($this->initialAddOns)) {
            return [];
        }

        return $this->toModifierArray($this->initialAddOns);
    }

    public function setAddOns(Modifiers $addOns): self
    {
        $this->addOns = $addOns;

        return $this;
    }

    public function setInitialAddOns(Modifiers $addOns): self
    {
        $this->initialAddOns = $addOns;

        return $this;
    }

    public function removeAddOn(string $id): self
    {
        $this->addOns->push(new AddOn($id), OperationType::remove());

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): self
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
     * @return StatusHistory[]
     */
    public function getStatusHistory(): array
    {
        return $this->statusHistory;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param StatusHistory[] $statusHistory
     */
    public function setStatusHistory(array $statusHistory): self
    {
        $this->statusHistory = $statusHistory;

        return $this;
    }

    public function getDaysPastDue(): int
    {
        return $this->daysPastDue;
    }

    public function setDaysPastDue(int $daysPastDue): self
    {
        $this->daysPastDue = $daysPastDue;

        return $this;
    }

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

    public function setPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

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

    public function resetPlan(Plan $plan): self
    {
        $this->previousPlan = null;
        $this->plan = new NullPlan();
        $this->setPlan($plan);

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

    public function getNextBillingPeriodAmount(): ?Money
    {
        if (isset($this->nextBillingPeriodAmount)) {
            return $this->nextBillingPeriodAmount;
        }

        return null;
    }

    public function setNextBillingPeriodAmount(Money $amount): self
    {
        $this->nextBillingPeriodAmount = $amount;

        return $this;
    }

    public function getNextBillingDate(): ?\DateTimeInterface
    {
        if ($this->is(Status::canceled())) {
            return null;
        }

        if (isset($this->nextBillingDate)) {
            return $this->nextBillingDate;
        }

        return null;
    }

    public function setNextBillingDate(?\DateTimeInterface $date): self
    {
        $this->nextBillingDate = $date;

        return $this;
    }

    private function setPriceFromPlan(Plan $plan): void
    {
        $plan->getPrice()->applyToSubscription($this);
    }

    public function setIsProrated(bool $isProrated): self
    {
        $this->isProrated = $isProrated;

        return $this;
    }

    public function isProrated(): bool
    {
        return $this->isProrated;
    }

    public function setTrial(Trial $trial): self
    {
        $this->trial = $trial;

        return $this;
    }

    public function getTrial(): ?Trial
    {
        if (isset($this->trial)) {
            return $this->trial;
        }

        return null;
    }

    /**
     * @return Modifier[]
     */
    private function toModifierArray(Modifiers $modifiers): array
    {
        return $modifiers
            ->filter(fn (Operation $op) => !$op->getType()->equals(OperationType::remove()))
            ->toModifierArray();
    }
}
