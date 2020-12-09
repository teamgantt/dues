<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use TeamGantt\Dues\Model\Subscription;

interface UpdateStrategy
{
    public function update(Subscription $subscription): ?Subscription;
}
