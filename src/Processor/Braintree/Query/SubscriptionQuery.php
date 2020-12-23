<?php

namespace TeamGantt\Dues\Processor\Braintree\Query;

use Braintree\Gateway;
use Braintree\SubscriptionSearch;
use TeamGantt\Dues\Exception\InvalidSubscriptionSearchParamException;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;

class SubscriptionQuery
{
    private Gateway $gateway;

    private SubscriptionMapper $mapper;

    /**
     * @var mixed[] Items appended must be supported search methods found in `\Braintree\SubscriptionSearch`
     */
    private array $searchParams = [];

    public function __construct(Gateway $gateway, SubscriptionMapper $mapper)
    {
        $this->gateway = $gateway;
        $this->mapper = $mapper;
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

        return $subscriptions;
    }
}
