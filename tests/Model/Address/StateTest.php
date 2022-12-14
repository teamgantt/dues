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

    public function testCreatingStateFromMissingEnumValue()
    {
        $this->expectException(\BadMethodCallException::class);
        new State('Quebec/Montreal');
    }

    public function testCreatingStateFromInvalidEnumValue()
    {
        $this->expectException(\TypeError::class);
        new State(null);
    }

    public function testCreatingStateFromMissingEnumOrInvalidEnumIsCaughtByThrowable()
    {
        $exception = 0;
        $typeError = 0;

        try {
            new State('Quebec/Montreal');
        } catch (\Throwable $e) {
            $exception = 1;
        }

        try {
            new State(null);
        } catch (\Throwable $e) {
            $typeError = 1;
        }

        $this->assertEquals(1, $exception, 'Enum exception not caught by Throwable');
        $this->assertEquals(1, $typeError, 'Enum type error not caught by Throwable');
    }
}
