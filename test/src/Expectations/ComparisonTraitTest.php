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
use Tisie\Expect\Expectations\ComparisonTrait;

/**
 * Testcase for \Tisie\Expect\Expectations\ComparisonTrait
 *
 * @covers \Tisie\Expect\Expectations\ComparisonTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class ComparisonTraitTest extends TestCase
{
    use SetupTargetTrait;

    /** @var array|object */
    private $target = [
        'default' => [
            'callback' => 'createDefaultTarget',
        ],
        'create' => [
            [
                'for' => 'testInstance*',
                'callback' => 'createTargetForInstance',
            ],
        ],
    ];

    private function createDefaultTarget()
    {
        return new class
        {
            public $value = 'TESTVALUE';

            use ComparisonTrait;

            public function assert($cond, $opts = null, $mthd = null)
            {
                return [$cond, $opts, $mthd];
            }
        };
    }

    public function methodNames()
    {
        return [
            ['equals', false, ['%namval% must be equal to %s', 'stringify' => true]],
            ['notEquals', true, ['%namval% must not be equal to %s', 'stringify' => true]],
            ['same', false, ['%namval% must be identical to %s', 'stringify' => true]],
            ['notSame', true, ['%namval% must not be identical to %s', 'stringify' => true]],
            ['empty', false, '%namval% must be empty'],
            ['notEmpty', true, '%namval% must not be empty'],
            ['null', false, '%name|Value% must be null'],
            ['notNull', true, '%name|Value% must not be null'],
        ];
    }

    /**
     * @dataProvider methodNames
     */
    public function testMethodsThatCallsAssertDirectly($name, $expectCond, $expectOpts = null, $expectMthd = null)
    {
        $actual = $this->target->$name('NOPE');
        $expect = [$expectCond, $expectOpts, $expectMthd];

        static::assertEquals($expect, $actual);
    }

    private function createTargetForInstance()
    {
        return new class
        {
            use ComparisonTrait {
                instanceFail as public;
            }
            public static $args = [];
            public $value;

            public static function arg($val)
            {
                static::$args['arg'][] = [$val];
                return new static();
            }
            public static function val($val, $name)
            {
                static::$args['val'][] = [$val, $name];
                return new static();
            }
            public function is(...$args)
            {
                static::$args['is'][] = $args;
                return $this;
            }

            public function assert(...$args)
            {
                return $args;
            }

        };
    }

    public function provideInstanceTestData()
    {
        return [
            [false, new \stdClass(), \stdClass::class, false, true],
            [false, \stdClass::class, \stdClass::class, true, true],
            [true, new \stdClass(), \stdClass::class, false, false],
            [true, \stdClass::class, \stdClass::class, true, false],
        ];
    }

    /**
     * @dataProvider provideInstanceTestData
     */
    public function testInstance($inverse = false, $value, $fqcn, $allow, $exCond, $exMthd = 'instanceFail')
    {
        $this->target->value = $value;
        $method = ($inverse ? 'not' : '') . 'instance';
        $actual = $this->target->$method($fqcn, $allow);

        static::assertEquals($exCond, $actual[0]);
        static::assertEquals([$fqcn, $inverse, $allow], $actual[1]);
        static::assertEquals($exMthd, $actual[2]);
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testInstanceFail($not)
    {
        $fqcn = $not ? new \stdClass() : \stdClass::class;
        $expect = sprintf(
            '%%name%% must %sbe an instance of %s{{, but is %%value%%}}',
            $not ? 'not ' : '',
            is_object($fqcn) ? get_class($fqcn) : $fqcn
        );
        $actual = $this->target->instanceFail($fqcn, $not);

        static::assertEquals($expect, $actual[0] ?? '');
    }
}
