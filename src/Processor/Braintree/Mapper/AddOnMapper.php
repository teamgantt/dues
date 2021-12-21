<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\AddOn as BraintreeAddOn;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Model\Modifier\AddOnBuilder;

class AddOnMapper
{
    use MapsModifiers;

    private AddOnBuilder $builder;

    public function __construct()
    {
        $this->builder = new AddOnBuilder();
    }

    public function fromResult(BraintreeAddOn $result): AddOn
    {
        $this->fromGenericResult($this->builder, $result);

        return $this->builder->build();
    }

    /**
     * @param BraintreeAddOn[] $results
     *
     * @return AddOn[]
     */
    public function fromResults(array $results): array
    {
        return array_map([$this, 'fromResult'], $results);
    }
}
