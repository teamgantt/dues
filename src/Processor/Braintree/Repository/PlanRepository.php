<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Processor\Braintree\Mapper\PlanMapper;

class PlanRepository
{
    protected PlanMapper $mapper;

    private Gateway $braintree;

    public function __construct(Gateway $braintree, PlanMapper $mapper)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
    }

    /**
     * @return Plan[]
     */
    public function all(): array
    {
        $results = $this
            ->braintree
            ->plan()
            ->all();

        return array_reduce($results, function ($r, $i) {
            $plan = $this->mapper->fromResult($i);

            return [...$r, $plan];
        }, []);
    }

    public function find(string $id): ?Plan
    {
        $plans = $this->all();

        foreach ($plans as $plan) {
            if ($plan->getId() === $id) {
                return $plan;
            }
        }

        return null;
    }
}
