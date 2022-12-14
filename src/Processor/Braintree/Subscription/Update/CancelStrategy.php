<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use TeamGantt\Dues\Exception\SubscriptionNotCanceledException;
use TeamGantt\Dues\Model\Subscription;

class CancelStrategy extends BaseUpdateStrategy
{
    public function update(Subscription $subscription): ?Subscription
    {
        try {
            $this
                ->braintree
                ->subscription()
                ->cancel($subscription->getId());

            return null;
        } catch (\Exception $e) {
            throw new SubscriptionNotCanceledException($e->getMessage());
        }
    }
}
