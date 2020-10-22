<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);
namespace Tisie\ExpectTest;

use Cross\TestUtils\TestCase\SetupTargetTrait;
use Cross\TestUtils\TestCase\TestInheritanceTrait;
use PHPUnit\Framework\TestCase;
use Tisie\Expect\AbstractExpect;
use Tisie\Expect\Exception\UnexpectedValueException;
use Tisie\Expect\InternalExpect;

/**
 * Testcase for \Tisie\Expect\InternalExpect
 *
 * @covers \Tisie\Expect\InternalExpect
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group
 */
class InternalExpectTest extends TestCase
{
    use SetupTargetTrait, TestInheritanceTrait;

    /**
     * @var array|\Reflection|InternalExpect
     */
    private $target = [
        'create' => [
            [
                'for' => 'testInheritance',
                'reflection' => InternalExpect::class,
            ],
            [
                'for' => [
                    'testThrowsCorrectException',
                    'testConstructorIsProtected',
                ]
                // no target instance
            ],
        ],
    ];

    private $inheritance = [ AbstractExpect::class ];

    private  function initTarget()
    {
        return new class extends InternalExpect
        {
            public $forwardArguments;

            public function __construct() {}
            protected function forward(string $expectation, array $options, ...$args)
            {
                $this->forwardArguments = [ $expectation, $options, $args ];
            }
        };
    }

    public function testValidOptionExpectation()
    {
        $key = 'test';
        $types = ['string', 'object'];
        $expect = [
            'is',
            [
                'throw' => 'value',
                'msg' => "Option key '$key' must be %s, but got %value%",
            ],
            $types
        ];

        $this->target->validOption($key, ...$types);

        static::assertEquals($expect, $this->target->forwardArguments);
    }

    public function testThrowsCorrectException()
    {
        $this->expectException(UnexpectedValueException::class);

        InternalExpect::val(true)->validOption('key', 'string');
    }

    public function testConstructorIsProtected()
    {
        $this->expectErrorMessageMatches('~protected.*::__construct~');

        new InternalExpect(true);
    }
}
