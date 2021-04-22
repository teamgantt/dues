<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Repository\PlanRepository;
use TeamGantt\Dues\Processor\Braintree\Repository\SubscriptionRepository;

abstract class BaseUpdateStrategy implements UpdateStrategy
{
    protected Gateway $braintree;

    protected SubscriptionMapper $mapper;

    protected SubscriptionRepository $subscriptions;

    protected PlanRepository $plans;

    public function __construct(
        Gateway $braintree,
        SubscriptionMapper $mapper,
        SubscriptionRepository $subscriptions,
        PlanRepository $plans)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
        $this->subscriptions = $subscriptions;
        $this->plans = $plans;
    }

    /**
     * @return Successful|Error
     */
    protected function doBraintreeUpdate(Subscription $subscription, ?Plan $newPlan = null)
    {
        $request = $this->mapper->toRequest($subscription, $newPlan);
        $request = Arr::dissoc($request, ['firstBillingDate', 'nextBillingPeriodAmount', 'status', 'statusHistory']);
        $request['options'] = ['prorateCharges' => true];

        return $this
            ->braintree
            ->subscription()
            ->update($request['id'], $request);
    }
}
