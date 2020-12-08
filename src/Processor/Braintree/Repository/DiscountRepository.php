<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Processor\Braintree\Mapper\DiscountMapper;

class DiscountRepository
{
    protected DiscountMapper $mapper;

    private Gateway $braintree;

    public function __construct(Gateway $braintree, DiscountMapper $mapper)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
    }

    /**
     * @return Discount[]
     */
    public function all(): array
    {
        $results = $this
            ->braintree
            ->discount()
            ->all();

        return $this->mapper->fromResults($results);
    }
}
