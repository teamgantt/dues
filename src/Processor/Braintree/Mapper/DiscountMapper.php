<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree;
use TeamGantt\Dues\Model\Subscription\Discount;
use TeamGantt\Dues\Model\Subscription\DiscountBuilder;

class DiscountMapper
{
    use MapsModifiers;

    private DiscountBuilder $builder;

    public function __construct()
    {
        $this->builder = new DiscountBuilder();
    }

    public function fromResult(Braintree\Discount $result): Discount
    {
        $this->fromGenericResult($this->builder, $result);

        return $this->builder->build();
    }

    /**
     * @param Braintree\Discount[] $results
     *
     * @return Discount[]
     */
    public function fromResults(array $results): array
    {
        return array_map([$this, 'fromResult'], $results);
    }
}
