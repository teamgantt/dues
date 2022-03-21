<?php

namespace TeamGantt\Dues\Tests\Feature;

use DateTime;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Tests\ProvidesTestData;

trait Transaction
{
    use ProvidesTestData;

    /**
     * @group integration
     * @group transactions
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testFindTransactionById(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (Subscription $s) => $s->beginImmediately());
        $transaction = $subscription->getTransactions()[0];

        $transaction = $this->dues->findTransactionById($transaction->getId());

        $this->assertTrue(!$transaction->isNew());
    }

    /**
     * @group integration
     * @group transactions
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

    /**
     * @group integration
     * @group transactions
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testFindTransactionsByCustomerIdAndStartDateAndEndDate(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (Subscription $s) => $s->beginImmediately());
        $customer = $subscription->getCustomer();

        $subscriptionYear = $subscription->getStartDate()->format('Y');
        $nextYear = $subscriptionYear + 1;
        $startDate = new DateTime($subscriptionYear.'-01-01');
        $endDate = new DateTime($nextYear.'-01-01');

        $transactions = $this->dues->findTransactionsByCustomerId($customer->getId(), $startDate, $endDate);
        $sample = $transactions[0];

        $this->assertTrue(!$sample->isNew());
    }

    /**
     * @group integration
     * @group transactions
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testFindTransactionsByCustomerIdAndStartDateAndEndDateWithNoResults(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (Subscription $s) => $s->beginImmediately());
        $customer = $subscription->getCustomer();

        $subscriptionYear = $subscription->getStartDate()->format('Y');
        $beginningOfYearAfterSubscription = $subscriptionYear + 1;
        $endOfYearAfterSubscription = $beginningOfYearAfterSubscription + 1;
        $startDate = new DateTime($beginningOfYearAfterSubscription.'-01-01');
        $endDate = new DateTime($endOfYearAfterSubscription.'-01-01');

        $transactions = $this->dues->findTransactionsByCustomerId($customer->getId(), $startDate, $endDate);

        $this->assertEmpty($transactions);
    }

    /**
     * @group integration
     * @group transactions
     * @dataProvider subscriptionProvider
     *
     * @return void
     */
    public function testFindTransactionsBySubscriptionId(callable $subscriptionFactory)
    {
        $subscription = $subscriptionFactory($this->dues, null, fn (Subscription $s) => $s->beginImmediately());

        $transactions = $this->dues->findTransactionsBySubscriptionId($subscription->getId());
        $sample = $transactions[0];

        $this->assertTrue(!$sample->isNew());
    }
}
