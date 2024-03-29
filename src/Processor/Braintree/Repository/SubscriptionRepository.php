<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Event\Dispatcher;
use TeamGantt\Dues\Event\EventType;
use TeamGantt\Dues\Exception\SubscriptionNotCreatedException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Processor\Braintree\Hydrator\SubscriptionHydrator;
use TeamGantt\Dues\Processor\Braintree\Mapper\SubscriptionMapper;
use TeamGantt\Dues\Processor\Braintree\Subscription\Update\UpdateStrategyFactory;

class SubscriptionRepository
{
    protected CustomerRepository $customers;

    protected PlanRepository $plans;

    protected SubscriptionMapper $mapper;

    private Gateway $braintree;

    private UpdateStrategyFactory $strategies;

    private SubscriptionHydrator $hydrator;

    private ?Dispatcher $events;

    public function __construct(
        Gateway $braintree,
        SubscriptionMapper $mapper,
        CustomerRepository $customers,
        PlanRepository $plans,
        SubscriptionHydrator $hydrator)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
        $this->customers = $customers;
        $this->plans = $plans;
        $this->hydrator = $hydrator;
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
            $this->dispatch(EventType::beforeCreateCustomer(), $customer);
            $subscription->setCustomer($this->customers->add($customer));
            $this->dispatch(EventType::afterCreateCustomer(), $subscription->getCustomer());
        }

        $this->dispatch(EventType::beforeCreateSubscription(), $subscription);
        $request = $this->mapper->toRequest($subscription, $plan);
        $request = Arr::dissoc($request, ['id', 'status', 'statusHistory', 'options']);

        $result = $this
            ->braintree
            ->subscription()
            ->create($request);

        if ($result->success) {
            $newSubscription = $this->mapper->fromResult($result->subscription);

            $result = $newSubscription
                ->setCustomer($subscription->getCustomer())
                ->setPlan($plan);

            $this->dispatch(EventType::afterCreateSubscription(), $result);

            return $result;
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
            $this->hydrator->hydrate([$subscription]);

            return $subscription;
        } catch (\Exception $e) {
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

        $this->hydrator->hydrate($subscriptions);

        if (empty($statuses)) {
            return $subscriptions;
        }

        return array_values(array_filter($subscriptions, fn (Subscription $sub) => in_array($sub->getStatus(), $statuses)));
    }

    public function setDispatcher(Dispatcher $events): void
    {
        $this->events = $events;
    }

    /**
     * @param Subscription|Customer $model
     */
    private function dispatch(EventType $type, $model): void
    {
        if (null === $this->events) {
            return;
        }

        $this->events->dispatch($type, $model);
    }
}
