<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use Exception;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Subscription\Update\UpdateStrategyFactory;

class SubscriptionRepository
{
    protected CustomerRepository $customers;

    protected PlanRepository $plans;

    protected SubscriptionMapper $mapper;

    private Gateway $braintree;

    private UpdateStrategyFactory $strategies;

    public function __construct(
        Gateway $braintree,
        SubscriptionMapper $mapper,
        CustomerRepository $customers,
        PlanRepository $plans)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
        $this->customers = $customers;
        $this->plans = $plans;
        $this->strategies = new UpdateStrategyFactory($this->braintree, $this->mapper, $this, $plans);
    }

    public function add(Subscription $subscription): Subscription
    {
        $customer = $subscription->getCustomer();
        $isNewCustomer = false;

        $plan = $subscription->getPlan();
        $plan = $this->plans->find($plan->getId());

        if (null === $plan) {
            throw new SubscriptionNotCreatedException('Failed to fetch subscription plan');
        }

        $subscription->setPlan($plan);

        if ($customer->isNew()) {
            $isNewCustomer = true;
            $subscription->setCustomer($this->customers->add($customer));
        }

        $request = $this->mapper->toRequest($subscription, $plan);
        $request = Arr::dissoc($request, ['id', 'status']);

        $result = $this
            ->braintree
            ->subscription()
            ->create($request);

        if ($result->success) {
            $newSubscription = $this->mapper->fromResult($result->subscription);

            return $newSubscription
                ->setCustomer($subscription->getCustomer())
                ->setPlan($plan);
        }

        if (!$isNewCustomer) {
            throw new SubscriptionNotCreatedException($result->message);
        }

        throw new SubscriptionNotCreatedException($result->message, $subscription->getCustomer());
    }

    public function find(string $id): ?Subscription
    {
        try {
            $result = $this
                ->braintree
                ->subscription()
                ->find($id);

            $subscription = $this->mapper->fromResult($result);
            $this->hydratePlans([$subscription]);

            return $subscription;
        } catch (Exception $e) {
            return null;
        }
    }

    public function update(Subscription $subscription): Subscription
    {
        $strategy = $this->strategies->createStrategy($subscription);

        if ($updated = $strategy->update($subscription)) {
            return $updated;
        }

        $updated = $this->find($subscription->getId());
        if (null === $updated) {
            throw new UnknownException('Updated subscription not found');
        }

        return $updated;
    }

    /**
     * @param Status[] $statuses
     *
     * @return Subscription[]
     */
    public function findByCustomerId(string $customerId, array $statuses = []): array
    {
        $customerResult = $this->customers->findBraintreeCustomer($customerId);

        if (null === $customerResult) {
            return [];
        }

        $subscriptions = Arr::mapcat($customerResult->paymentMethods, function ($pm) {
            return array_map([$this->mapper, 'fromResult'], $pm->subscriptions);
        });

        $this->hydratePlans($subscriptions);

        if (empty($statuses)) {
            return $subscriptions;
        }

        return array_values(array_filter($subscriptions, fn (Subscription $sub) => in_array($sub->getStatus(), $statuses)));
    }

    /**
     * @param Subscription[] $subscriptions
     */
    private function hydratePlans(array $subscriptions): void
    {
        $cache = [];

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->getPlan();

            $hydrated = isset($cache[$plan->getId()]) ? $cache[$plan->getId()] : $this->plans->find($plan->getId());

            if (null === $hydrated) {
                continue;
            }

            if (!isset($cache[$hydrated->getId()])) {
                $cache[$hydrated->getId()] = $hydrated;
            }

            $subscription->setPlan($hydrated);
        }
    }
}
