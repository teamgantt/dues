<?php

namespace TeamGantt\Dues\Tests\Feature;

use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Tests\ProvidesTestData;

trait Transaction
{
    use ProvidesTestData;

    /**
     * @group integration
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testFindTransactionsByCustomerId(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (Subscription $s) => $s->beginImmediately());
        $customer = $subscription->getCustomer();

        $transactions = $this->dues->findTransactionsByCustomerId($customer->getId());
        $sample = $transactions[0];

        $this->assertTrue(!$sample->isNew());
    }
}
