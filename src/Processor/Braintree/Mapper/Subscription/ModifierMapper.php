<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper\Subscription;

use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Modifier\Modifier;
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

            return [
                $this->withRemovedPreviousPlanDefaults($addOns, $previousPlan->getAddOns()),
                $this->withRemovedPreviousPlanDefaults($discounts, $previousPlan->getDiscounts()),
            ];
        }

        return $modifiers;
    }

    /**
     * @return mixed[]
     */
    private function toAddOnsAndDiscountsRequest(Subscription $subscription, Plan $plan): array
    {
        return [
            $this->toModifiersRequest($subscription->getAddOnsImpl(), $plan),
            $this->toModifiersRequest($subscription->getDiscountsImpl(), $plan),
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
        }

        return $request;
    }

    /**
     * Determines how modifiers are applied to a new Subscription in Braintree.
     *
     * @return mixed[]
     */
    private function toModifiersRequest(Modifiers $modifiers, Plan $plan): array
    {
        $add = [];
        $update = [];
        $remove = [];

        $operations = $modifiers->getOperations();

        foreach ($operations as $operation) {
            $modifier = $operation->getModifier();
            $type = $operation->getType();
            $default = $plan->getModifier($modifier->getId());

            if ($type->equals(OperationType::add()) && null === $default) {
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
