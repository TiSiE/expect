<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);
namespace Tisie\ExpectTest\Expectations;

use ArrayIterator;
use Cross\TestUtils\TestCase\SetupTargetTrait;
use PHPUnit\Framework\TestCase;
use Tisie\Expect\Expectations\IterableTrait;

/**
 * Testcase for \Tisie\Expect\Expectations\IterableTraitTest
 *
 * @covers \Tisie\Expect\Expectations\IterableTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class IterableTraitTest extends TestCase
{
    private $target;
    public $isCalledWith = [];

    public function setup(): void
    {
        $this->target = new class
        {
            use IterableTrait {
                oneOfFail as public;
                subsetFail as public;
                supersetFail as public;
            }

            public $value;

            public function assert(...$args)
            {
                return $args;
            }

            public function is(...$args)
            {
                $this->isCalledWith[] = $args;
                return true;
            }

            public static function stringify($v)
            {
                return (string) $v;
            }
        };
    }

    public function provideOneOfTestData()
    {
        return [
            [[1,2,3], false, [false, 'oneOfFail']],
            [[1,2,3,'TESTVALUE'], false, [true, 'oneOfFail']],
            [new ArrayIterator([1,2,3]), true, [true, 'oneOfFail']],
            [new ArrayIterator([1,2,3, 'TESTVALUE']), true, [false, 'oneOfFail']],
        ];
    }

    /**
     * @dataProvider provideOneOfTestData
     */
    public function testOneOf($choices, $not, $expect)
    {
        $this->target->value = 'TESTVALUE';
        $method = ($not ? 'not' : '') . 'oneof';
        $actual = $this->target->$method($choices);

        static::assertEquals($expect, $actual);
    }

    /**
     * @testWith [[1,2,3], false]
     *           [[1,2,3], true]
     */
    public function testOneOfFail($value, $not)
    {
        $actual = $this->target->oneOfFail($value, $not);

        static::assertIsArray($actual);
        static::assertArrayHasKey(0, $actual);
        static::assertEquals($actual[0], "%namval% must %sbe one of \n\n - %s");
        static::assertArrayHasKey('vars', $actual);
        static::assertIsArray($actual['vars']);
        static::assertCount(2, $actual['vars']);
        static::assertArrayHasKey(0, $actual['vars']);
        static::assertEquals($not ? 'not ' : '', $actual['vars'][0]);
        static::assertArrayHasKey(1, $actual['vars']);
        static::assertStringContainsString('- 2', $actual['vars'][1]);
    }

    public function provideTestSubsetData()
    {
        return [
            [[1,3,5], [1,2,3,4,5], false, true, []],
            [[1,2,3], [2,3,4,5,6], false, false, [1]],
            [['a' => 1, 'b' => 2], ['a' => 1, 'c' => 3, 'b' => 2], true, true, []],
            [['a' => 1], ['a' => 2], true, false, ['a' => 1]],
            [new ArrayIterator([1,3,5]), new ArrayIterator([1,2,3,4,5]), false, true, []],
            [new ArrayIterator([1,2,3]), new ArrayIterator([2,3,4,5,6]), false, false, [1]],
        ];
    }

    /**
     * @dataProvider provideTestSubsetData
     */
    public function testSubset($val, $set, $assoc, $expectCond, $expectDiff)
    {
        $this->target->value = $val;
        $expectVal = $val instanceof \Traversable ? iterator_to_array($val) : $val;
        $expectSet = $set instanceof \Traversable ? iterator_to_array($set) : $set;
        $expect = [$expectCond, [$expectVal, $expectSet, $expectDiff, $assoc]];
        $actual = $this->target->subset($set, $assoc);

        static::assertCount(1, $this->target->isCalledWith[0] ?? [], 'Method "is" is expected to be called exactly once.');
        static::assertEquals('iterable', $this->target->isCalledWith[0][0], '"is" was not called with expected value');
        static::assertEquals($expect, $actual);
    }

    public function provideTestSubsetFailData()
    {
        return [
            [ [1,2,3], [2,3,4,5,6], [1], false, ['+ 2', '+ 3', '- 1']],
            [ ['a' => 1, 'b' => 3], ['a' => 1, 'b' => 2], ['b' => 3], true,  ['Not part of the set', '- [b] => 3', '+ [a] => 1'] ],
        ];
    }
    /**
     * @dataProvider provideTestSubsetFailData
     */
    public function testSubsetFail($val, $set, $diff, $assoc, $expect)
    {
        $this->target->value = $val;
        $result = $this->target->subsetFail($val, $set, $diff, $assoc);

        static::assertIsArray($result);
        static::assertCount(3, $result);
        static::assertArrayHasKey(0, $result);
        static::assertArrayHasKey('vars', $result);
        static::assertIsArray($result['vars'], 'vars is not array.');
        static::assertCount(1, $result['vars'], 'vars array has wrong count');
        static::assertArrayHasKey(0, $result['vars'], 'vars array has no items');
        static::assertArrayHasKey('throw', $result);
        static::assertEquals("%name|array% is not a subset of: \n\n%s", $result[0], 'Message is wrong.');
        static::assertEquals('domain', $result['throw']);

        foreach ($expect as $needle) {
            static::assertStringContainsString($needle, $result['vars'][0]);
        }
    }

    public function provideTestSupersetData()
    {
        return [
            [[1,2,3,4,5], [1,3,5], false, true, [1,3,5]],
            [[1,2,3,4,5], [6,3,8], false, false, [3]],
            [['a' => 1, 'b' => '2', 'c' => '3'], ['a' => 1], true, true, ['a' => 1]],
            [['a' => 1, 'b' => '2', 'c' => '3'], ['a' => '2', 'c' => '3'], true, false, ['c' => '3']],
            [new ArrayIterator([1,2,3,4,5]), new ArrayIterator([1,3,5]), false, true, [1,3,5]],
            [new ArrayIterator(['a' => 1, 'b' => '2', 'c' => '3']), new ArrayIterator(['a' => 1]), true, true, ['a' => 1]],
        ];
    }

    /**
     * @dataProvider provideTestSupersetData
     */
    public function testSuperset($val, $set, $assoc, $expectCond, $expectArr, $isIterator = false)
    {
        $this->target->value = $val;
        $expectSet = $set instanceof \Traversable ? iterator_to_array($set) : $set;
        $expect = [$expectCond, [$expectSet, $expectArr, $assoc]];
        $actual = $this->target->superset($set, $assoc);

        static::assertCount(1, $this->target->isCalledWith[0] ?? [], 'Method "is" is expected to be called exactly once.');
        static::assertEquals('iterable', $this->target->isCalledWith[0][0], '"is" was not called with expected value');
        static::assertEquals($expect, $actual);
    }

    public function provideTestSupersetFailData()
    {
        return [
            [ [2,3,8,9], [2,3], false, ['+ 2', '+ 3', '- 8', '- 9']],
            [ ['a' => 1, 'b' => 3], ['a' => 1], true,  ['- [b] => 3', '+ [a] => 1'] ],
        ];
    }
    /**
     * @dataProvider provideTestSupersetFailData
     */
    public function testSupersetFail($set, $intersect, $assoc, $expect)
    {
        $result = $this->target->supersetFail($set, $intersect, $assoc);

        static::assertIsArray($result);
        static::assertCount(3, $result);
        static::assertArrayHasKey(0, $result);
        static::assertArrayHasKey('vars', $result);
        static::assertIsArray($result['vars'], 'vars is not array.');
        static::assertCount(1, $result['vars'], 'vars array has wrong count');
        static::assertArrayHasKey(0, $result['vars'], 'vars array has no items');
        static::assertArrayHasKey('throw', $result);
        static::assertEquals("%name|array% is not a superset of:\n\n%s", $result[0], 'Message is wrong.');
        static::assertEquals('domain', $result['throw']);

        foreach ($expect as $needle) {
            static::assertStringContainsString($needle, $result['vars'][0]);
        }
    }
}
