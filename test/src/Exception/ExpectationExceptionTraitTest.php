<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);
namespace Tisie\ExpectTest\Exception;

use PHPUnit\Framework\TestCase;
use Tisie\Expect\AbstractExpect;
use Tisie\Expect\Exception\ExpectationExceptionTrait;

/**
 * Testcase for \Tisie\Expect\Exception\ExpectationExceptionTrait
 *
 * @covers \Tisie\Expect\Exception\ExpectationExceptionTrait
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group Tisie.Expect
 * @group Tisie.Expect.Exception
 * @group Tisie.Expect.ExpectationExceptionTrait
 */
class ExpectationExceptionTraitTest extends TestCase
{

    public function testInstantiation()
    {
        $target = new class('TestMessage') extends \Exception
        {
            use ExpectationExceptionTrait;
        };

        static::assertEquals('TestMessage', $target->getMessage());
    }

    private  function createTarget($trace, $template = null)
    {
        return new class ($trace, $template)
        {
            use ExpectationExceptionTrait;

            public $template;

            public function __construct($trace, $template)
            {
                $this->fulltrace = $trace;
                $this->template = $template;
            }

            public function getTraceAsString()
            {
                return '--- trace as string ---';
            }

            public function getCode()
            {
                return 19;
            }

            public function getMessage()
            {
                return '--- message ---';
            }

        };
    }

    public function testStringRenderingWithDefaultTemplate()
    {
        $context = new class
        {
            public function testMethod()
            {

            }
        };

        $trace = [
            ['function' => '__construct'],
            ['object' => $context],
            ['object' => $context],
            [
                'class' => get_class($context),
                'type' => '->',
                'function' => 'testMethod',
                'file' => 'path/testfile.php',
                'line' => 19,
                'args' => []
            ],
        ];

        $target = $this->createTarget($trace);
        $actual = $target->__toString();
        static::assertStringContainsString(get_class($context) . '->testMethod', $actual);

        $target = $this->createTarget($trace, '%message%');
        static::assertEquals($target->getMessage(), $target->__toString());
    }

    public function testGatherFulltraceInfosWithContextChangeAndPublicMethod()
    {
        $context = new class
        {
            public function testMethod()
            {

            }
        };
        $invoker = new class
        {
            protected function firstMethod() {}
            public function secondMethod() {}
        };

        $trace = [
            ['function' => '__construct'],
            ['object' => $context],
            ['object' => $context, 'function' => 'shouldNotBother'],
            ['object' => $invoker, 'function' => 'firstMethod'],
            [
                'object' => $invoker,
                'function' => 'secondMethod',
                'type' => '->',
                'file' => 'path/test.file',
                'line' => 19,
                'args' => []
            ],
        ];

        $target = $this->createTarget($trace);

        static::assertStringContainsString(get_class($invoker) . '->secondMethod', $target->__toString());
        static::assertStringContainsString('path/test.file', $target->__toString());
    }

    public function testGatherFulltraceInfosUsePreviousTraceItemWhenFalse()
    {
        $context = new class
        {
            protected function testMethod()
            {

            }
        };
        $invoker = new class
        {
            protected function firstMethod() {}
            protected function secondMethod() {}
        };

        $trace = [
            ['function' => '__construct'],
            ['object' => $context],
            ['object' => $context, 'function' => 'shouldNotBother'],
            ['object' => $invoker, 'function' => 'firstMethod'],
            [
                'object' => $invoker,
                'function' => 'secondMethod',
                'type' => '->',
                'file' => 'path/test.file',
                'line' => 19,
                'args' => []
            ],
        ];

        $target = $this->createTarget($trace);

        static::assertStringContainsString('<script>', $target->__toString());
        static::assertStringContainsString('path/test.file', $target->__toString());
    }

    public function testGatherFulltraceInfosOnlyUseInvokedFunction()
    {
        $context = new class
        {
            protected function testMethod()
            {

            }
            public static function stringify($v)
            {
                return $v;
            }
        };
        $invoker = new class
        {
            protected function firstMethod() {}
            protected function secondMethod() {}
        };

        $trace = [
            ['function' => '__construct'],
            ['object' => $context],
            ['object' => $context, 'function' => 'shouldNotBother'],
            ['object' => $invoker, 'function' => 'firstMethod'],
            [
                'object' => null,
                'class' => null,
                'function' => '\is_string',
                'type' => '',
                'file' => 'path/test.file',
                'line' => 19,
                'args' => []
            ],
        ];

        $target = $this->createTarget($trace);

        static::assertStringContainsString('\is_string', $target->__toString());
        static::assertStringContainsString('path/test.file', $target->__toString());
    }

    public function testGatherFulltraceInfosWithFunctionArguments()
    {
        $context = new class
        {
            public static function stringify($v)
            {
                return AbstractExpect::stringify($v);
            }
        };
        $invoker = new class
        {
            public function invoked(?string $str = 'actual', ...$args): string
            {
                return $str;
            }
        };

        $trace = [
            ['function' => '__construct'],
            ['object' => $context],
            ['object' => $context, 'function' => 'shouldNotBother'],
            [
                'object' => $invoker,
                'function' => 'invoked',
                'type' => '->',
                'file' => 'path/test.file',
                'line' => 19,
                'args' => ['str' => 'actual']
            ],
        ];

        $target = $this->createTarget($trace);

        $actual = $target->__toString();
        static::assertStringContainsString('[?string $str =', $actual);
    }

}
