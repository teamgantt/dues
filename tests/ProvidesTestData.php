<?php

namespace TeamGantt\Dues\Tests;

use TeamGantt\Dues\Dues;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Customer\CustomerBuilder;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\Subscription\SubscriptionBuilder;

trait ProvidesTestData
{
    /**
     * Returns data as a function that produces an ID and whether the
     * expected result is null or not.
     */
    public function customerProvider()
    {
        $existing = (new CustomerBuilder())
            ->withFirstName('Henry')
            ->withLastName('GanttSr')
            ->withEmailAddress(uniqid('dues').'@teamgantt.com')
            ->withPaymentMethod(new Nonce('fake-valid-nonce'))
            ->build();

        $new = (new CustomerBuilder())
            ->withFirstName('Henry')
            ->withLastName('GanttJr')
            ->withEmailAddress(uniqid('dues').'@teamgantt.com')
            ->withPaymentMethod(new Nonce('fake-valid-nonce'))
            ->build();

        return [
            'existing customer' => [fn ($dues) => $dues->createCustomer($existing)],
            'new customer' => [fn () => $new],
        ];
    }

    public function subscriptionProvider()
    {
        ['existing customer' => [$customerFactory]] = $this->customerProvider();

        $factory = function (Dues $dues, ?Customer $customer = null, ?callable $cb = null) use ($customerFactory) {
            $customer = $customer ?? $customerFactory($dues);
            $callback = $cb ?? fn ($x) => $x;
            $addOns = $dues->listAddOns();
            $plan = $dues->findPlanById('test-plan-c-yearly');
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $startDate = $now->add(new \DateInterval('P1D')); // start 1 day in the future

            $subscription = (new SubscriptionBuilder())
                ->withAddOn($addOns[0])
                ->withCustomer($customer)
                ->withPlan($plan)
                ->withStartDate($startDate)
                ->build();

            return $dues->createSubscription($callback($subscription));
        };

        return ['test-plan-c-yearly' => [$factory]];
    }

    /**
     * Returns data as a function that produces an ID and whether the
     * expected result is null or not.
     */
    public function customerByValidityProvider()
    {
        $customer = (new CustomerBuilder())
            ->withFirstName('Henry')
            ->withLastName('Gantt')
            ->withEmailAddress('henry.gantt@teamgantt.com')
            ->withPaymentMethod(new Nonce('fake-valid-mastercard-nonce'))
            ->build();

        return [
            'valid user' => [fn ($dues) => $dues->createCustomer($customer)->getId(), false],
            'invalid user' => [fn () => time(), true],
        ];
    }
}
