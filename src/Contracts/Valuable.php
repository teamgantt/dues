<?php

namespace TeamGantt\Dues\Contracts;

use TeamGantt\Dues\Model\Money;

interface Valuable
{
    public function getValue(): Money;
}
