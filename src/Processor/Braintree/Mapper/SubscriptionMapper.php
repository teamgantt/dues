<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Exception\UnknownStatusException;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\PaymentMethod\NullPaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Processor\Braintree\Mapper\Subscription\ModifierMapper;

class SubscriptionMapper
{
    private AddOnMapper $addOnMapper;

    private DiscountMapper $discountMapper;

    private TransactionMapper $transactionMapper;

    private ModifierMapper $modifierMapper;

    private StatusHistoryMapper $statusHistoryMapper;

    public function __construct(
        AddOnMapper $addOnMapper,
        DiscountMapper $discountMapper,
        TransactionMapper $transactionMapper,
        StatusHistoryMapper $statusHistoryMapper
    ) {
        $this->addOnMapper = $addOnMapper;
        $this->discountMapper = $discountMapper;
        $this->transactionMapper = $transactionMapper;
        $this->statusHistoryMapper = $statusHistoryMapper;
        $this->modifierMapper = new ModifierMapper();
    }

    /**
     * @return mixed[]
     */
    public function toRequest(Subscription $subscription, ?Plan $plan = null): array
    {
        $request = Arr::replaceKeys($subscription->toArray(), [
            'payment' => 'paymentMethodToken',
            'plan' => 'planId',
            'startDate' => 'firstBillingDate',
        ]);

        $request['options'] = ['prorateCharges' => $request['isProrated']];

        $request = Arr::dissoc($request, ['customer', 'daysPastDue', 'isProrated']);

        $request = Arr::updateIn($request, [], function (array $r) {
            if (isset($r['paymentMethodToken']['token'])) {
                $r['paymentMethodToken'] = $r['paymentMethodToken']['token'];
            }

            if (isset($r['planId']['id'])) {
                $r['planId'] = $r['planId']['id'];
            }

            if (isset($r['price'])) {
                $r['price'] = $r['price']['amount'];
            }

            return $r;
        });

        return $this->withModifiers($request, $subscription, $plan);
    }

    /**
     * @param mixed[] $request
     *
     * @return mixed[]
     */
    private function withModifiers(array $request, Subscription $subscription, ?Plan $plan): array
    {
        list($addOns, $discounts) = $this->modifierMapper->toRequest($subscription, $plan);
        if (!empty($addOns)) {
            $request['addOns'] = $addOns;
        }

        if (!empty($discounts)) {
            $request['discounts'] = $discounts;
        }

        return $request;
    }

    public function fromResult(Braintree\Subscription $result): Subscription
    {
        $builder = new SubscriptionBuilder();
        $balance = new Money(floatval($result->balance));
        $price = new Price(floatval($result->price));
        $status = $this->getStatusFromResult($result);
        $daysPastDue = $result->daysPastDue ?? 0;
        $paymentMethod = $result->paymentMethodToken
            ? new Token($result->paymentMethodToken)
            : new NullPaymentMethod();
        $plan = new Plan($result->planId);
        $addOns = $this->addOnMapper->fromResults($result->addOns);
        $discounts = $this->discountMapper->fromResults($result->discounts);
        $statusHistory = $this->statusHistoryMapper->fromResults($result->statusHistory);

        $billingPeriodStart = $result->billingPeriodStartDate;
        $billingPeriodEnd = $result->billingPeriodEndDate;

        $subscription = $builder
            ->withId($result->id)
            ->withStartDate($result->firstBillingDate)
            ->withBalance($balance)
            ->withPrice($price)
            ->withStatus($status)
            ->withStatusHistory($statusHistory)
            ->withDaysPastDue($daysPastDue)
            ->withPaymentMethod($paymentMethod)
            ->withBillingPeriod($billingPeriodStart, $billingPeriodEnd)
            ->withPlan($plan);

        if (isset($result->nextBillingPeriodAmount)) {
            $subscription->withNextBillingPeriodAmount(new Money(floatval($result->nextBillingPeriodAmount)));
        }

        $subscription = $subscription->build();

        foreach ($result->transactions as $transaction) {
            $subscription->addTransaction($this->transactionMapper->fromResult($transaction));
        }

        $subscription->setAddOns(new Modifiers($addOns));
        $subscription->setInitialAddOns(new Modifiers($addOns));
        $subscription->setDiscounts(new Modifiers($discounts));
        $subscription->setInitialDiscounts(new Modifiers($discounts));

        return $this->setRemainingValue($subscription);
    }

    protected function setRemainingValue(Subscription $sub): Subscription
    {
        $billingPeriod = $sub->getBillingPeriod();

        if (null === $billingPeriod) {
            return $sub;
        }

        $cost = $sub->getValue()->getAmount();
        $cycle = $billingPeriod->getBillingCycle();
        $remainingCycle = $billingPeriod->getRemainingBillingCycle();

        $costPerDay = $cost / $cycle;
        $remainingValue = round($costPerDay * $remainingCycle, 2);

        return $sub->setRemainingValue(new Money($remainingValue));
    }

    protected function getStatusFromResult(Braintree\Subscription $result): Status
    {
        $status = $result->status;

        switch ($status) {
            case Braintree\Subscription::ACTIVE:
                return Status::active();
            case Braintree\Subscription::CANCELED:
                return Status::canceled();
            case Braintree\Subscription::EXPIRED:
                return Status::expired();
            case Braintree\Subscription::PAST_DUE:
                return Status::pastDue();
            case Braintree\Subscription::PENDING:
                return Status::pending();
            default:
                throw new UnknownStatusException("Unknown status of $status received");
        }
    }
}
