<?php

namespace TeamGantt\Dues\Processor\Braintree\Query;

use Braintree\Gateway;
use Braintree\Subscription as BraintreeSubscription;
use Braintree\SubscriptionSearch;
use TeamGantt\Dues\Exception\InvalidSubscriptionSearchParamException;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Hydrator\SubscriptionHydrator;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;

class SubscriptionQuery
{
    private Gateway $gateway;

    private SubscriptionMapper $mapper;

    private SubscriptionHydrator $hydrator;

    /**
     * @var mixed[] Items appended must be supported search methods found in `\Braintree\SubscriptionSearch`
     */
    private array $searchParams = [];

    public function __construct(Gateway $gateway, SubscriptionMapper $mapper, SubscriptionHydrator $hydrator)
    {
        $this->gateway = $gateway;
        $this->mapper = $mapper;
        $this->hydrator = $hydrator;
    }

    public function whereDaysPastDue(string $comparison, int $days): self
    {
        if ('<=' === $comparison) {
            $this->searchParams['daysPastDue'] = SubscriptionSearch::daysPastDue()->lessThanOrEqualTo($days);
        } elseif ('=' === $comparison) {
            $this->searchParams['daysPastDue'] = SubscriptionSearch::daysPastDue()->is($days);
        } elseif ('>=' === $comparison) {
            $this->searchParams['daysPastDue'] = SubscriptionSearch::daysPastDue()->greaterThanOrEqualTo($days);
        } else {
            throw new InvalidSubscriptionSearchParamException('Comparisons can only be "<=", "=", or ">="');
        }

        return $this;
    }

    public function whereNextBillingDateIs(\DateTimeInterface $date): self
    {
        $this->searchParams['nextBillingDate'] = SubscriptionSearch::nextBillingDate()->is($date);

        return $this;
    }

    public function whereSubscriptionIsPending(): self
    {
        $this->searchParams['status'] = SubscriptionSearch::status()->in([BraintreeSubscription::PENDING]);

        return $this;
    }

    public function getById(string $id): ?Subscription
    {
        if (empty($id)) {
            return null;
        }

        try {
            $find = $this->gateway->subscription()->find($id);

            $subscription = $this->mapper->fromResult($find);
            $this->hydrator->hydrate([$subscription]);

            return $subscription;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return Subscription[]
     */
    public function fetch(): array
    {
        $subscriptions = [];
        $collection = $this->gateway->subscription()->search(array_values($this->searchParams));

        foreach ($collection as $subscription) {
            $subscriptions[] = $this->mapper->fromResult($subscription);
        }

        $this->hydrator->hydrate($subscriptions);

        return $subscriptions;
    }
}
