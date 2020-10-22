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

use Cross\TestUtils\TestCase\SetupTargetTrait;
use PHPUnit\Framework\TestCase;
use Tisie\Expect\Expectations\NumericTrait;

/**
 * Testcase for \Tisie\Expect\Expectations\NumericTrait
 *
 * @covers \Tisie\Expect\Expectations\NumericTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class NumericTraitTest extends TestCase
{
    use SetupTargetTrait;

    /**
     * @var array|object
     */
    private $target = [
        'create' => [
            [
                'for' => 'testCompareForward',
                'callback' => 'createTargetForCompareForwardTests',
            ],
            [
                'for' => ['testIntervalFail'],
                'callback' => 'createTargetForIntervalFailTests',
            ],
            [
                'for' => ['testCompare', 'testInterval*'],
                'callback' => 'createTargetForCompareTests',
            ],
        ],
    ];

    private function createTargetForCompareForwardTests()
    {
        return new class
        {
            use NumericTrait;

            protected function compare($limit, $mode)
            {
                return [$limit, $mode];
            }
        };
    }

    /**
     * @testWith    ["gt"]
     *              ["gte"]
     *              ["lt"]
     *              ["lte"]
     *              ["eq"]
     *              ["ne"]
     */
    public function testCompareForward($method)
    {
        $value = 10;
        $expect = [(float) $value, $method];
        $actual = $this->target->$method($value);

        static::assertEquals($expect, $actual);
    }

    private function createTargetForCompareTests()
    {
        return new class
        {
            use NumericTrait;

            public $value = 10, $wasIsCalledCorrectly;

            public function assert(...$args)
            {
                return $args;
            }

            public function is(...$args)
            {
                $this->wasIsCalledCorrectly = ($args[0] ?? "") == 'numeric';
                return $this;
            }
        };
    }

    /**
     * @testWith    ["gt", true, "be greater than"]
     *              ["gte", true, "be greater than or equal to"]
     *              ["lt", false, "be lower than"]
     *              ["lte", false, "be lower than or equal to"]
     *              ["eq", false, "be equal to"]
     *              ["ne", true, "be not equal to"]
     */
    public function testCompare($method, $expectCond, $expectMsg)
    {
        [$actualCond, [0 => $actualMsg, 'throw' => $actualThrow]] = $this->target->$method(9);

        static::assertTrue(
            $this->target->wasIsCalledCorrectly,
            'Checking value type "numeric" does not gets called correctly'
        );
        static::assertEquals($expectCond, $actualCond, 'Compare returns wrong condition value');
        static::assertEquals('domain', $actualThrow);
        static::assertStringContainsString($expectMsg, $actualMsg);
    }

    /**
     * @testWith    [1, 2, true, null, false, true, true]
     *              [1, 2, false, null, false, false, false]
     *              [8, 12, true, false, true, true, false]
     *              [9, 10, false, true, true, false, true]
     *              [10, 45, false, null, false, false, false]
    */
    public function testInterval($min, $max, $minInc, $maxInc, $expectCond, $expectMinInc, $expectMaxInc)
    {
        [$actualCond, $actualInc] = $this->target->interval($min, $max, $minInc, $maxInc);

        static::assertTrue(
            $this->target->wasIsCalledCorrectly,
            'Checking value type "numeric" does not gets called correctly'
        );
        static::assertEquals($expectCond, $actualCond, 'Condition value is incorrect');
        static::assertEquals([$expectMinInc, $expectMaxInc], $actualInc, 'Include flags not correct.');
    }

    /**
     * @testWith    [1, 2, true, null, true, true, true]
     *              [1, 2, false, null, true, false, false]
     *              [8, 12, true, false, false, true, false]
     *              [9, 10, false, true, false, false, true]
     *              [10, 45, false, null, true, false, false]
    */
    public function testIntervalInversed($min, $max, $minInc, $maxInc, $expectCond, $expectMinInc, $expectMaxInc)
    {
        [$actualCond, $actualInc, $actualFailMethod] =
            $this->target->notInterval($min, $max, $minInc, $maxInc);

        static::assertTrue(
            $this->target->wasIsCalledCorrectly,
            'Checking value type "numeric" does not gets called correctly'
        );
        static::assertEquals($expectCond, $actualCond, 'Condition value is incorrect');
        static::assertEquals([$expectMinInc, $expectMaxInc, true], $actualInc, 'Include flags not correct.');
        static::assertEquals('intervalFail', $actualFailMethod);
    }

    private function createTargetForIntervalFailTests()
    {
        return new class
        {
            use NumericTrait {
                intervalFail as public;
            }
        };
    }

    /**
     * @testWith    [true, true, true]
     *              [true, true, false]
     *              [true, false, true]
     *              [false, true, true]
     *              [true, false, false]
     *              [false, true, false]
     *              [false, false, true]
     *              [false, false, false]
     */
    public function testIntervalFail($minInc, $maxInc, $not)
    {
        $actual = $this->target->intervalFail($minInc, $maxInc, $not);

        if ($not) {
            static::assertStringContainsString('not ', $actual);
        } else {
            static::assertStringNotContainsString('not ', $actual);
        }

        $intervalExpect =
            ($minInc ? '[' : ']')
            . '%s, %s'
            . ($maxInc ? ']' : '[')
        ;

        static::assertStringContainsString($intervalExpect, $actual);
    }
}
