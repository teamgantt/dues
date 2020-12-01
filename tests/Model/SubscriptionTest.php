<?php

namespace TeamGantt\Dues\Model\Tests;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Subscription\Status;

final class SubscriptionTest extends TestCase
{
    public function testIsNotStatus()
    {
        $cancelled = new Subscription();
        $cancelled->setStatus(Status::canceled());
        $active = new Subscription();
        $active->setStatus(Status::active());

        $this->assertTrue($cancelled->isNot(Status::active()));
        $this->assertFalse($active->isNot(Status::active()));
    }
}
