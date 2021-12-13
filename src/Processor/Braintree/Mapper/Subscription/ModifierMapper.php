<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper\Subscription;

use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Modifier\ModifierType;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Modifier\OperationType;
use TeamGantt\Dues\Model\Subscription\Modifiers;

class ModifierMapper
{
    /**
     * @return mixed[]
     */
    public function toRequest(Subscription $subscription, ?Plan $newPlan = null): array
    {
        $plan = empty($newPlan) ? $subscription->getPlan() : $newPlan;

        if ($subscription->isNew()) {
            return $this->toAddOnsAndDiscountsRequest($subscription, $plan);
        }

        $modifiers = $this->toAddOnsAndDiscountsRequest($subscription, $plan);

        if ($previousPlan = $subscription->getPreviousPlan()) {
            list($addOns, $discounts) = $modifiers;

            $newAddOns = $this->withRemovedPreviousPlanDefaults($addOns, $previousPlan->getAddOns());
            $newDiscounts = $this->withRemovedPreviousPlanDefaults($discounts, $previousPlan->getDiscounts());

            $this->withNewDefaultModifiers($newAddOns, $plan->getAddOns());
            $this->withNewDefaultModifiers($newDiscounts, $plan->getDiscounts());

            return [
                $newAddOns,
                $newDiscounts,
            ];
        }

        return $modifiers;
    }

    /**
     * @param mixed[]    $request
     * @param Modifier[] $newModifiers
     */
    private function withNewDefaultModifiers(array &$request, array $newModifiers): void
    {
        foreach ($newModifiers as $newDefault) {
            $request = Arr::updateIn($request, ['add'], function ($modifiers) use ($newDefault) {
                $default = Arr::replaceKeys($newDefault->toArray(), ['id' => 'inheritedFromId']);

                if (is_array($modifiers)) {
                    $otherUserSuppliedModifiers = Arr::filter($modifiers, fn ($modifier) => $modifier['inheritedFromId'] !== $default['inheritedFromId']);
                    $defaultModifierWithProvidedUpdates = [array_merge(
                        $default,
                        ...Arr::filter($modifiers, fn ($modifier) => $modifier['inheritedFromId'] === $default['inheritedFromId']) ?? []
                    )];

                    return array_merge($otherUserSuppliedModifiers, $defaultModifierWithProvidedUpdates);
                }

                return [$default];
            });
        }
    }

    /**
     * @return mixed[]
     */
    private function toAddOnsAndDiscountsRequest(Subscription $subscription, Plan $plan): array
    {
        return [
            $this->toModifiersRequest($subscription, $plan, ModifierType::addOn()),
            $this->toModifiersRequest($subscription, $plan, ModifierType::discount()),
        ];
    }

    /**
     * @param mixed[]    $request
     * @param Modifier[] $defaults
     *
     * @return mixed[]
     */
    private function withRemovedPreviousPlanDefaults(array &$request, array $defaults): array
    {
        foreach ($defaults as $default) {
            if (!isset($request['remove'])) {
                $request['remove'] = [];
            }
            $request['remove'][] = $default->getId();

            // removed Modifiers can't be in the update
            if (isset($request['update'])) {
                $request['update'] = Arr::filter($request['update'], fn ($m) => $m['existingId'] !== $default->getId());
            }
        }

        return $request;
    }

    /**
     * Determines if a modifier should be added or modified on an existing Subscription based off of the Plan defaults & which modifiers are already on the Subscription.
     */
    private function isModifierAddedOnExistingSubscription(bool $isDefaultModifier, Subscription $subscription, ModifierType $modifierType, Modifier $modifier): bool
    {
        $hasFnName = $modifierType->equals(ModifierType::addOn()) ? 'hasAddOn' : 'hasDiscount';
        $previousPlanFnName = $modifierType->equals(ModifierType::addOn()) ? 'getInitialAddOns' : 'getInitialDiscounts';

        /**
         * @var bool
         */
        $planHasModifier = $subscription->getPlan()->{$hasFnName}($modifier->getId());

        /**
         * @var Modifier[]
         */
        $subscriptionPreviousModifiers = $subscription->{$previousPlanFnName}();

        $isAdding = $isDefaultModifier || $planHasModifier;

        if (!$isAdding) {
            return false;
        }

        $isModifierOnExistingSubscription = array_reduce(
            $subscriptionPreviousModifiers,
            fn (bool $isExisting, Modifier $m) => $isExisting || $m->getId() === $modifier->getId(),
            false
        );

        if ($isModifierOnExistingSubscription) {
            return false;
        }

        return true;
    }

    /**
     * Determines how modifiers are applied to a new Subscription in Braintree.
     *
     * @return mixed[]
     */
    private function toModifiersRequest(Subscription $subscription, Plan $plan, ModifierType $modifierType): array
    {
        $add = [];
        $update = [];
        $remove = [];

        $operations = $modifierType->equals(ModifierType::addOn()) ? $subscription->getAddOnsImpl()->getOperations() : $subscription->getDiscountsImpl()->getOperations();

        foreach ($operations as $operation) {
            $modifier = $operation->getModifier();
            $type = $operation->getType();
            $default = $plan->getModifier($modifier->getId());
            $isAdding = null === $default;

            // when changing plans, allow modifications to default AddOns
            if (!$subscription->isNew()) {
                $isAdding = $this->isModifierAddedOnExistingSubscription($isAdding, $subscription, $modifierType, $modifier);
            }

            if ($type->equals(OperationType::add()) && true === $isAdding) {
                $add[] = Arr::replaceKeys($modifier->toArray(), ['id' => 'inheritedFromId']);
            } elseif ($type->equals(OperationType::remove())) {
                $remove[] = $modifier->getId();
                continue;
            } elseif ($modifier->isEqualTo($default)) {
                continue;
            } else {
                $update[] = Arr::replaceKeys($modifier->toArray(), ['id' => 'existingId']);
            }
        }

        $modifiers = [];

        if (!empty($add)) {
            $modifiers['add'] = $add;
        }

        if (!empty($update)) {
            $modifiers['update'] = $update;
        }

        if (!empty($remove)) {
            $modifiers['remove'] = $remove;
        }

        return $modifiers;
    }
}
