<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use TeamGantt\Dues\Exception\IllegalStateException;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Modifiers;
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
        $previousPlan = $original->getPreviousPlan();

        if (null === $previousPlan) {
            throw new IllegalStateException('Previous plan should not be null while changing billing cycles.');
        }

        $nextAddOns = $this->removePreviousPlanDefaultModifiers($original->getAddOnsImpl(), $previousPlan->getAddOns());
        $nextDiscounts = $this->removePreviousPlanDefaultModifiers($original->getDiscountsImpl(), $previousPlan->getDiscounts());

        $builder = (new SubscriptionBuilder())
                ->withPlan($original->getPlan())
                ->withCustomer($original->getCustomer())
                ->withDiscounts($nextDiscounts)
                ->withAddOns($nextAddOns);

        if ($balance = $canceled->getBalance()) {
            $balanceDiscount = new Discount('balance', 1, new Price(abs($balance->getAmount())));

            $builder->withDiscount($balanceDiscount);
        }

        if (!empty($original->getPrice())) {
            $builder->withPrice($original->getPrice());
        }

        $newSubscription = $builder->build();

        $discounts = $newSubscription->getDiscountsImpl();
        $addOns = $newSubscription->getAddOnsImpl();

        return $newSubscription
            ->merge($canceled)
            ->setCustomer($original->getCustomer())
            ->setAddOns($addOns)
            ->setDiscounts($discounts);
    }

    /**
     * @param Modifier[] $defaultModifiers
     *
     * @return Modifier[]
     */
    private function removePreviousPlanDefaultModifiers(Modifiers $modifiers, array $defaultModifiers): array
    {
        foreach ($defaultModifiers as $modifier) {
            $modifiers->drop($modifier);
        }

        return $modifiers->toModifierArray();
    }

    private function cancel(Subscription $original): Subscription
    {
        $previousPlan = $original->getPreviousPlan();

        if (null === $previousPlan) {
            throw new IllegalStateException('Previous plan should not be null while changing billing cycles.');
        }

        // Close out the subscription, removing any price information
        $clone = (new Subscription($original->getId()))->merge($original);
        $clone->setPlan($previousPlan);
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
