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
use Tisie\Expect\Expectations\ClassConstantsTrait;

/**
 * Testcase for \Tisie\Expect\Expectations\ClassConstantsTrait
 *
 * @covers \Tisie\Expect\Expectations\ClassConstantsTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class ClassConstantsTraitTest extends TestCase
{
    private $target;

    public function setUp(): void
    {
        $this->target = new class()
        {
            use ClassConstantsTrait {
                constantFail as traitConstantFail;
            }

            public $called = [];
            public $value;

            public function __call($method, $args)
            {
                $this->called[] = [$method, $args];
            }

            public function constantFail($class, $constants)
            {
                return $this->traitConstantFail($class, $constants);
            }
        };
    }

    /**
     * @testWith ["TEST", {"TEST_VALUE_ONE": "one", "TEST_VALUE_TWO": "two"}]
     *           ["", {"TEST_VALUE_ONE": "one", "TEST_VALUE_TWO": "two", "TSET_OTHER": "other"}]
     */
    public function testMethodConstantCallsAssert(string $prefix, array $expectedArgs)
    {
        $dummy = new class()
        {
            public const TEST_VALUE_ONE = 'one';
            public const TEST_VALUE_TWO = 'two';
            public const TSET_OTHER = 'other';
        };

        $this->target->value = 'one';
        $this->target->constant($dummy, $prefix);

        $called = $this->target->called;
        static::assertArrayHasKey(0, $called, 'It seems that no method was called...');
        [$method, $args] = $called[0];
        static::assertEquals('assert', $method, 'Wrong method was called.');
        static::assertTrue($args[0], 'Wrong value for assert arg');
        static::assertTrue(count($args) == 2 && is_array($args[1]) && count($args[1]) == 2, 'Called with wrong args');
        static::assertInstanceOf(\ReflectionClass::class, $args[1][0]);
        static::assertEquals(get_class($dummy), $args[1][0]->getName());
        static::assertEquals($expectedArgs, $args[1][1]);
    }

    public function testMethodConstantFailReturnsExpectedArray()
    {
        $class = new class {};
        $refl = new \ReflectionClass($class);
        $constants = [
            'TEST_ONE' => 'one',
            'TEST_TWO' => 'two',
        ];

        $result = $this->target->constantFail($refl, $constants);

        static::assertIsArray($result);
        static::assertStringStartsWith('%namval% must be one of', $result[0]);
        static::assertArrayHasKey('vars', $result);
        static::assertCount(1, $result['vars']);
        static::assertStringContainsString(get_class($class) . '::TEST_ONE', $result['vars'][0]);
        static::assertArrayHasKey('throw', $result);
        static::assertEquals('domain', $result['throw']);
    }

}
