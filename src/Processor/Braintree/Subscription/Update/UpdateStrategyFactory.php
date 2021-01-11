<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use Braintree\Gateway;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\SubscriptionRepository;

class UpdateStrategyFactory
{
    private Gateway $braintree;

    private SubscriptionMapper $mapper;

    private SubscriptionRepository $subscriptions;

    private PlanRepository $plans;

    public function __construct(
        Gateway $braintree,
        SubscriptionMapper $mapper,
        SubscriptionRepository $subscriptions,
        PlanRepository $plans
    ) {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
        $this->subscriptions = $subscriptions;
        $this->plans = $plans;
    }

    public function createStrategy(Subscription $subscription): UpdateStrategy
    {
        $braintree = $this->braintree;
        $mapper = $this->mapper;
        $subscriptions = $this->subscriptions;
        $plans = $this->plans;

        if ($subscription->is(Status::canceled())) {
            return new CancelStrategy($braintree, $mapper, $subscriptions, $plans);
        }

        $changeBillingCycle = new ChangeBillingCycleStrategy($braintree, $mapper, $subscriptions, $plans);

        if ($subscription->is(Status::pastDue())) {
            return new PastDueUpdateStrategy($braintree, $mapper, $subscriptions, $plans, $changeBillingCycle);
        }

        return new DefaultUpdateStrategy($braintree, $mapper, $subscriptions, $plans, $changeBillingCycle);
    }
}
