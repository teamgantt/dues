<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use TeamGantt\Dues\Exception\IllegalStateException;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\Discount;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Price\NullPrice;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Modifiers;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;
use TeamGantt\Dues\Model\Subscription\Trial\Trial;
use TeamGantt\Dues\Model\Subscription\Trial\TrialUnit;

class ChangeBillingCycleStrategy extends BaseUpdateStrategy
{
    public function update(Subscription $subscription): ?Subscription
    {
        // Make a new subscription to replace old one
        $newSubscription = $this->createReplacementSubscription($subscription);
        $newSubscription = $this->subscriptions->add($newSubscription);

        // Cancel original subscription
        $canceled = $this->cancel($subscription);

        // Update the state of the given subscription to reflect canceled state
        $subscription->merge($canceled);

        // Return the new replacement subscription
        return $newSubscription;
    }

    private function createReplacementSubscription(Subscription $original): Subscription
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

        if ($modifier = $this->getNewPurchaseModifier($original)) {
            if ($modifier instanceof Discount) {
                $builder->withDiscount($modifier);
            }

            if ($modifier instanceof AddOn) {
                $builder->withAddOn($modifier);
            }
        }

        if (!empty($original->getPrice())) {
            $builder->withPrice($original->getPrice());
        }

        $newSubscription = $builder->build();

        $discounts = $newSubscription->getDiscountsImpl();
        $addOns = $newSubscription->getAddOnsImpl();

        $newSubscription
            ->merge($original)
            ->setCustomer($original->getCustomer())
            ->setAddOns($addOns)
            ->setDiscounts($discounts);

        $newSubscription = $this->handleFirstBillingDate($newSubscription);
        $newSubscription = $this->handleTrialPeriod($newSubscription, $original);

        return $newSubscription;
    }

    /**
     * Braintree doesn't allow firstBillingDates in the past.
     * When upgrading an existing subscription to new billing cycle,
     * clear the startDate if the startDate is in the past.
     */
    private function handleFirstBillingDate(Subscription $subscription): Subscription
    {
        $today = new \DateTime('utc');

        if ($subscription->getStartDate() > $today) {
            return $subscription;
        }

        return $subscription->beginImmediately();
    }

    /**
     * Preserve the original trial period when a trial subscription changes billing cycles.
     */
    private function handleTrialPeriod(Subscription $newSubscription, Subscription $originalSubscription): Subscription
    {
        $isInTrial = $originalSubscription->getTrial() instanceof Trial;
        $hasStartDate = $originalSubscription->getStartDate() instanceof \DateTime;

        if (!$isInTrial || !$hasStartDate) {
            return $newSubscription;
        }

        $newTrialDuration = $this->findDaysUntilNextTrialStart($originalSubscription->getStartDate());
        $newTrial = new Trial($newTrialDuration, TrialUnit::day());

        // Clear the first billing date, as you cannot define
        // a first billing date & a trial period.
        $newSubscription->beginImmediately();

        return $newSubscription->setTrial($newTrial);
    }

    private function findDaysUntilNextTrialStart(?\DateTime $originalStartDate): int
    {
        if (!($originalStartDate instanceof \DateTime)) {
            return 0;
        }

        $today = new \DateTimeImmutable('UTC');
        $startOfToday = $today->setTime(0, 0, 0);
        $trialEndDate = $originalStartDate;

        $difference = $trialEndDate->diff($startOfToday);
        if (!($difference instanceof \DateInterval)) {
            return 0;
        }

        $remainingTrialDays = $difference->days;
        if (is_bool($remainingTrialDays)) {
            return 0;
        }

        return $remainingTrialDays;
    }

    private function getNewPurchaseModifier(Subscription $sub): ?Modifier
    {
        $balance = $sub->getBalance();
        $modValue = $sub->getRemainingValue()->getAmount();

        if (null !== $balance && 0.0 !== $balance->getAmount()) {
            if ($balance->getAmount() < 0.0) {
                $modValue += abs($balance->getAmount());
            } elseif ($balance->getAmount() > 0.0) {
                $modValue -= abs($balance->getAmount());
            }
        }

        if (0.0 === $modValue) {
            return null;
        }

        if ($modValue >= 0.0) {
            return new Discount('balance', 1, new Price($modValue));
        }

        return new AddOn('overdue', 1, new Price(abs($modValue)));
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

        $subscription = (new Subscription($original->getId()))->merge($original);
        $subscription->setPlan($previousPlan);

        // Cancel subscription
        return $this->subscriptions->update($subscription->cancel())
            ->closeOut()
            ->setPrice(new NullPrice());
    }
}
