<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use TeamGantt\Dues\Model\Modifier\AddOn;
use TeamGantt\Dues\Processor\Braintree\Mapper\AddOnMapper;

class AddOnRepository
{
    protected AddOnMapper $mapper;

    private Gateway $braintree;

    public function __construct(Gateway $braintree, AddOnMapper $mapper)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
    }

    /**
     * @return AddOn[]
     */
    public function all(): array
    {
        $results = $this
            ->braintree
            ->addOn()
            ->all();

        return $this->mapper->fromResults($results);
    }
}
