<?php

namespace TeamGantt\Dues\Tests;

use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Arr;

final class ArrTest extends TestCase
{
    public function testUpdateIn()
    {
        $array = ['plan' => ['id' => 'one']];

        $updated = Arr::updateIn($array, ['plan'], fn ($a) => $a['id']);

        $this->assertEquals(['plan' => 'one'], $updated);
    }

    public function testUpdateInDeep()
    {
        $array = [
            'flip' => 'flam',
            'plan' => [
                'foo' => [
                    'bar' => true,
                ],
            ],
        ];

        $updated = Arr::updateIn($array, ['plan', 'foo', 'bar'], fn ($b) => !$b);

        $this->assertFalse($updated['plan']['foo']['bar']);
    }

    public function testMapcat()
    {
        $coll = [1, 3, 5, 7];

        $mapped = Arr::mapcat($coll, fn ($x) => [$x, $x + 1]);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $mapped);
    }

    public function testSelectKeys()
    {
        $coll = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];

        $evens = Arr::selectKeys($coll, ['two', 'four']);

        $this->assertEquals(['two' => 2, 'four' => 4], $evens);
    }

    public function testDissoc()
    {
        $coll = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];

        $evens = Arr::dissoc($coll, ['one', 'three']);

        $this->assertEquals(['two' => 2, 'four' => 4], $evens);
    }

    public function testUpdateInWithInvalidKeys()
    {
        $array = ['foo' => 'bar'];

        $updated = Arr::updateIn($array, ['flim', 'flam', 'jam'], fn () => 1);

        $this->assertArrayHasKey('foo', $updated);
        $this->assertEquals([
            'flam' => [
                'jam' => 1,
            ],
            ], $updated['flim']);
    }

    public function testUpdatingRootArray()
    {
        $array = [
            'payment' => [
                'token' => 'abc',
            ],
        ];

        $updated = Arr::updateIn($array, [], fn ($a) => Arr::assocIn($a, ['paymentToken'], $a['payment']['token']));
        unset($updated['payment']);

        $this->assertEquals(['paymentToken' => 'abc'], $updated);
    }

    public function testAssocInCreatesMissingKey()
    {
        $array = [];
        $result = Arr::assocIn($array, ['foo'], 'bar');

        $this->assertEquals(['foo' => 'bar'], $result);
    }
}
