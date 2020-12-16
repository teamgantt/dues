<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use Braintree\Error\Codes;
use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Exception;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\SubscriptionRepository;

class DefaultUpdateStrategy extends BaseUpdateStrategy
{
    private ChangeBillingCycleStrategy $changeBillingCycleStrategy;

    public function __construct(
        Gateway $braintree,
        SubscriptionMapper $mapper,
        SubscriptionRepository $subscriptions,
        PlanRepository $plans,
        ChangeBillingCycleStrategy $changeBillingCycleStrategy
    ) {
        parent::__construct($braintree, $mapper, $subscriptions, $plans);
        $this->changeBillingCycleStrategy = $changeBillingCycleStrategy;
    }

    public function update(Subscription $subscription): ?Subscription
    {
        try {
            $newPlan = $subscription->hasChangedPlans() ? $this->plans->find($subscription->getPlan()->getId()) : null;
            $result = $this->doBraintreeUpdate($subscription, $newPlan);

            if ($this->isBillingFrequencyError($result)) {
                return $this->changeBillingCycleStrategy->update($subscription);
            }

            if ($result instanceof Error) {
                $message = isset($result->message) ? $result->message : 'An unknown update error occurred';
                throw new SubscriptionNotUpdatedException($message);
            }

            $updated = $this->subscriptions->find($subscription->getId());

            if (null == $updated) {
                throw new UnknownException('Failed to find updated Subscription');
            }

            return $subscription->merge($updated);
        } catch (Exception $e) {
            throw new SubscriptionNotUpdatedException($e->getMessage());
        }
    }

    /**
     * @param Successful|Error $result
     */
    private function isBillingFrequencyError($result): bool
    {
        if ($result->success || !$result instanceof Error) {
            return false;
        }

        return !empty(array_filter(
            $result->errors->deepAll(),
            fn ($error) => Codes::SUBSCRIPTION_PLAN_BILLING_FREQUENCY_CANNOT_BE_UPDATED === $error->code
        ));
    }
}
