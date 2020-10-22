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

use PHPUnit\Framework\TestCase;
use Tisie\Expect\Expectations\StringTrait;

/**
 * Testcase for \Tisie\Expect\Expectations\StringTrait
 *
 * @covers \Tisie\Expect\Expectations\StringTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class StringTraitTest extends TestCase
{

    public function setUp(): void
    {
        $this->target = new class
        {
            use StringTrait;

            public $value = 'This is a test string';
            public $isCalledWith = [];
            public $assertCalledWith = [];

            public function is(...$args)
            {
                $this->isCalledWith[] = $args;
                return $this;
            }

            public function assert(...$args)
            {
                $this->assertCalledWith[] = $args;
                return $this;
            }
        };
    }

    /**
     * @testWith
     *      ["strlen", 10, "be exactly"]
     *      ["minlen", 120, "be at least"]
     *      ["maxlen", 10, "not be more than"]
     */
    public function testStringLengthMethods($method, $length, $expectMsg)
    {
        static::assertSame($this->target, $this->target->$method($length));
        static::assertEquals([['string']], $this->target->isCalledWith, '"is" is not called correctly.');
        static::assertCount(1, $this->target->assertCalledWith);
        static::assertArrayHasKey(1, $this->target->assertCalledWith[0]);
        static::assertArrayHasKey('msg', $this->target->assertCalledWith[0][1]);
        static::assertStringContainsString($expectMsg, $this->target->assertCalledWith[0][1]['msg']);
        static::assertTrue(($this->target->assertCalledWith[0][1]['throw'] ?? '') === 'domain', '"throw" is not "domain"');
    }

    /**
     * @testWith
     *      ["This", true]
     *      ["That", false]
     */
    public function testStartWithString($str, $expectCond)
    {
        static::assertSame($this->target, $this->target->startWithString($str));
        static::assertEquals([['string']], $this->target->isCalledWith, '"is" is not called correctly.');
        $expectAssert = [
            [
                $expectCond,
                [
                    '%namval% must start with "%s"',
                    'throw' => 'domain',
                ]
            ]
        ];
        static::assertEquals($expectAssert, $this->target->assertCalledWith);
    }

    /**
     * @testWith
     *      ["string", true]
     *      ["strong", false]
     */
    public function testEndWithString($str, $expectCond)
    {
        static::assertSame($this->target, $this->target->endWithString($str));
        static::assertEquals([['string']], $this->target->isCalledWith, '"is" is not called correctly.');
        $expectAssert = [
            [
                $expectCond,
                [
                    '%namval% must end with "%s"',
                    'throw' => 'domain',
                ]
            ]
        ];
        static::assertEquals($expectAssert, $this->target->assertCalledWith);
    }

    /**
     * @testWith
     *      ["~^this~i", true]
     *      ["~STRING$~", false]
     */
    public function testRegex($str, $expectCond)
    {
        static::assertSame($this->target, $this->target->regex($str));
        static::assertEquals([['string']], $this->target->isCalledWith, '"is" is not called correctly.');
        $expectAssert = [
            [
                $expectCond,
                [
                    '%namval% must match regular expression "%s"',
                    'throw' => 'domain',
                ]
            ]
        ];
        static::assertEquals($expectAssert, $this->target->assertCalledWith);
    }
}
