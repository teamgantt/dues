<?php

namespace TeamGantt\Dues\Processor\Braintree;

use Braintree;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Exception\UnknownStatusException;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;

class SubscriptionMapper
{
    private AddOnMapper $addOnMapper;

    private DiscountMapper $discountMapper;

    public function __construct(AddOnMapper $addOnMapper, DiscountMapper $discountMapper)
    {
        $this->addOnMapper = $addOnMapper;
        $this->discountMapper = $discountMapper;
    }

    /**
     * @return mixed[]
     */
    public function toRequest(Subscription $subscription): array
    {
        $request = Arr::replaceKeys($subscription->toArray(), [
            'payment' => 'paymentMethodToken',
            'plan' => 'planId',
            'startDate' => 'firstBillingDate',
        ]);

        unset($request['customer']);

        return Arr::updateIn($request, [], function (array $r) use ($subscription) {
            $r['paymentMethodToken'] = $r['paymentMethodToken']['token'] ?? null;
            $r['planId'] = $r['planId']['id'] ?? null;
            if (isset($r['price'])) {
                $r['price'] = $r['price']['amount'];
            }
            $this->withModifiers($r, 'addOns', $subscription);
            $this->withModifiers($r, 'discounts', $subscription);

            return $r;
        });
    }

    /**
     * @param mixed[] $request
     * @param string  $kind    - addOns|discounts
     */
    private function withModifiers(array &$request, string $kind, Subscription $subscription): void
    {
        if (!isset($request[$kind])) {
            return;
        }

        $request[$kind] = Arr::replaceKeys($request[$kind], [
            'new' => 'add',
            'current' => 'update',
            'removed' => 'remove',
        ]);

        foreach ($request[$kind] as $key => $values) {
            if ('add' === $key) {
                $request[$kind][$key] = array_map(fn ($m) => Arr::replaceKeys($m, ['id' => 'inheritedFromId']), $values);
            }

            if ('update' === $key) {
                $request[$kind][$key] = array_map(fn ($m) => Arr::replaceKeys($m, ['id' => 'existingId']), $values);
            }
        }
    }

    public function fromResult(Braintree\Subscription $result): Subscription
    {
        $builder = new SubscriptionBuilder();
        $price = new Price(floatval($result->price));
        $status = $this->getStatusFromResult($result);
        $paymentMethod = new Token($result->paymentMethodToken);
        $plan = new Plan($result->planId);
        $addOns = $this->addOnMapper->fromResults($result->addOns);
        $discounts = $this->discountMapper->fromResults($result->discounts);

        $subscription = $builder
            ->withId($result->id)
            ->withStartDate($result->firstBillingDate)
            ->withPrice($price)
            ->withStatus($status)
            ->withPaymentMethod($paymentMethod)
            ->withPlan($plan)
            ->build();

        $subscription->setAddOns(new Modifiers($subscription, $addOns));

        return $subscription->setDiscounts(new Modifiers($subscription, $discounts));
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
