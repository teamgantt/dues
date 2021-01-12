<?php

namespace TeamGantt\Dues\Tests\Model;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\CreditCardType;

final class CreditCardTypeTest extends TestCase
{
    public function testCreditCardTypeLabel()
    {
        $amex = CreditCardType::americanExpress();
        $this->assertEquals('American Express', $amex->label);
    }
}
