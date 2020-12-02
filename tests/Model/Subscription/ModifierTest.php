<?php

namespace TeamGantt\Dues\Model\Subscription\Tests;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\Price;
use TeamGantt\Dues\Model\Subscription\AddOn;

final class ModifierTest extends TestCase
{
    public function testTwoModifiersAreEqualIfTheyHaveSameValues()
    {
        $addOn1 = new AddOn('id', 1, new Price(1.00));
        $addOn2 = new AddOn('id', 1, new Price(1.00));
        $addOn3 = new AddOn('id', 1, new Price(2.00));
        $addOn4 = new AddOn('id', 2, new Price(1.00));

        $this->assertTrue($addOn1->isEqualTo($addOn2));
        $this->assertFalse($addOn1->isEqualTo($addOn3));
        $this->assertFalse($addOn1->isEqualTo($addOn4));
    }
}
