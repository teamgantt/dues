<?php

namespace TeamGantt\Dues\Tests\Model\Address;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Model\Address\State;

final class StateTest extends TestCase
{
    public function testValue()
    {
        $al = State::Alabama();
        $this->assertEquals('AL', $al->value);
    }

    public function testCreationFromIsoCode()
    {
        $state = new State('MI');
        $this->assertEquals('Michigan', $state->label);
    }
}
