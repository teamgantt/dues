<?php

namespace TeamGantt\Dues\Tests\Processor\Braintree\Mapper;

use Braintree\Transaction;
use Braintree\Transaction\CustomerDetails;
use DateTime;
use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Processor\Braintree\Mapper\AddOnMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\DiscountMapper;
use TeamGantt\Dues\Processor\Braintree\Mapper\TransactionMapper;

final class TransactionMapperTest extends TestCase
{
    public function testSettingNullPlan()
    {
        $addOns = new AddOnMapper();
        $discounts = new DiscountMapper();
        $mapper = new TransactionMapper($addOns, $discounts);
        $result = Transaction::factory([
            'id' => '123',
            'subscriptionId' => '12345',
            'status' => Transaction::AUTHORIZED,
            'amount' => '10.00',
            'createdAt' => new DateTime(),
            'planId' => null,
            'type' => Transaction::SALE,
        ]);
        $result->customerDetails = new CustomerDetails([
            'id' => '123',
            'email' => 'dev@test.com',
            'firstName' => 'Dev',
            'lastName' => 'Test',
        ]);

        $mapped = $mapper->fromResult($result);

        $this->assertNotNull($mapped);
    }
}
