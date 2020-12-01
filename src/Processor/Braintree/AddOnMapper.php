<?php

namespace TeamGantt\Dues\Processor\Braintree;

use Braintree;
use TeamGantt\Dues\Model\Subscription\AddOn;
use TeamGantt\Dues\Model\Subscription\AddOnBuilder;

class AddOnMapper
{
    use MapsModifiers;

    private AddOnBuilder $builder;

    public function __construct()
    {
        $this->builder = new AddOnBuilder();
    }

    public function fromResult(Braintree\AddOn $result): AddOn
    {
        $this->fromGenericResult($this->builder, $result);

        return $this->builder->build();
    }

    /**
     * @param Braintree\AddOn[] $results
     *
     * @return AddOn[]
     */
    public function fromResults(array $results): array
    {
        return array_map([$this, 'fromResult'], $results);
    }
}
