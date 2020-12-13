<?php

namespace TeamGantt\Dues\Model\Subscription\Modifier;

use TeamGantt\Dues\Model\Modifier\Modifier;

class Operation
{
    private OperationType $type;

    private Modifier $modifier;

    public function __construct(OperationType $type, Modifier $modifier)
    {
        $this->type = $type;
        $this->modifier = $modifier;
    }

    public function getModifier(): Modifier
    {
        return $this->modifier;
    }

    public function getType(): OperationType
    {
        return $this->type;
    }
}
