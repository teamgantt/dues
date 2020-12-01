<?php

namespace TeamGantt\Dues\Tests;

use DateInterval;
use DateTime;
use DateTimeZone;
use TeamGantt\Dues\Dues;
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

        $factory = function (Dues $dues) use ($customerFactory) {
            $customer = $customerFactory($dues);
            $addOns = $dues->listAddOns();
            $plan = $dues->findPlanById('test-plan-c-yearly');
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $startDate = $now->add(new DateInterval('P1D')); // start 1 day in the future

            $subscription = (new SubscriptionBuilder())
                ->withAddOn($addOns[0])
                ->withCustomer($customer)
                ->withPlan($plan)
                ->withStartDate($startDate)
                ->build();

            return $dues->createSubscription($subscription);
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
