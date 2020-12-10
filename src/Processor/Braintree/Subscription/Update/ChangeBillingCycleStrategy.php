<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;

class ChangeBillingCycleStrategy extends BaseUpdateStrategy
{
    public function update(Subscription $subscription): ?Subscription
    {
        // Zero out and cancel the subscription in order to get a balance
        $canceled = $this->cancel($subscription);

        // Make a new subscription to replace old one
        $newSubscription = $this->createReplacementSubscription($subscription, $canceled);

        // Update the state of the given subscription to reflect canceled state
        $subscription->merge($canceled);

        // Save and return the new replacement subscription
        return $this->subscriptions->add($newSubscription);
    }

    private function createReplacementSubscription(Subscription $original, Subscription $canceled): Subscription
    {
        // Purchase new subscription, applying balance from previous subscription.
        $builder = (new SubscriptionBuilder())
                ->withPlan($original->getPlan())
                ->withCustomer($original->getCustomer())
                ->withDiscounts($original->getDiscounts()->getAll())
                ->withAddOns($original->getAddOns()->getAll());

        if ($balance = $canceled->getBalance()) {
            $balanceDiscount = new Discount('balance', 1, new Price(abs($balance->getAmount())));

            $builder->withDiscount($balanceDiscount);
        }

        if (!empty($original->getPrice())) {
            $builder->withPrice($original->getPrice());
        }

        $newSubscription = $builder->build();

        $discounts = $newSubscription->getDiscounts();
        $addOns = $newSubscription->getAddOns();

        return $newSubscription
            ->merge($canceled)
            ->setCustomer($original->getCustomer())
            ->setAddOns($addOns)
            ->setDiscounts($discounts);
    }

    private function cancel(Subscription $original): Subscription
    {
        // Close out the subscription, removing any price information
        $clone = (new Subscription($original->getId()))->merge($original);
        $subscription = $clone->closeOut();
        $result = $this->doBraintreeUpdate($subscription);

        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'An unknown update error occurred';
            throw new SubscriptionNotUpdatedException($message);
        }
        $updated = $this->subscriptions->find($subscription->getId());

        if (null === $updated) {
            throw new UnknownException('Failed to fetch updated subscription');
        }

        // Cancel subscription
        return $this->subscriptions->update($updated->cancel())
            ->closeOut()
            ->setPrice(new NullPrice());
    }
}
