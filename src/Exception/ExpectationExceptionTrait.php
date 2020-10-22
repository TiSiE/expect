<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);

namespace Tisie\Expect\Exception;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

use function array_keys;
use function array_map;
use function debug_backtrace;
use function get_class;
use function join;
use function next;
use function str_replace;

use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait ExpectationExceptionTrait
{
    private $fulltrace;
    private $rendered;

    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->fulltrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    }

    public function __toString(): string
    {
        if ($this->rendered) {
            return $this->rendered;
        }

        $tmpl =
            $this->template
            ?? "\n\n== %type% (%code%)\n\n%message%\n\n--\n\n"
                . "%invoked%%params% \n"
                . "in %file% "
                . "on line %line%\n\n--\n\n"
                . "%trace%\n\n"
        ;

        [
            $invokedClass,
            $invokedType,
            $invokedFunc,
            $invokedFile,
            $invokedLine,
            $invokedArgs,
            $context,
        ] = $this->gatherFulltraceInfos();
        $params = $this->gatherInvokedFunctionParams($invokedClass, $invokedFunc, $invokedArgs, $context);


        $variables = [
            '%type%' => get_class($this),
            '%code%' => $this->getCode(),
            '%message%' => $this->getMessage(),
            '%invoked%' =>
                ($invokedClass ?: '')
                . ($invokedType ?: '')
                . $invokedFunc,
            '%params%' => $params,
            '%file%' => $invokedFile,
            '%line%' => $invokedLine,
            '%trace%' => $this->getTraceAsString(),
        ];

        return $this->rendered = str_replace(array_keys($variables), $variables, $tmpl);
    }

    private function gatherFulltraceInfos()
    {
        $traces = $this->fulltrace;
        $trace = next($traces); // skip '__construct'

        $context = $trace['object'];

        do {
            $prevTrace = $trace;
            $trace = next($traces);
        } while (
            isset($trace['object'])
            && (
                $trace['object'] === $context
                || (
                    isset($trace['function']) && isset($trace['object'])
                    && !(new ReflectionMethod($trace['object'], $trace['function']))
                        ->isPublic()
                )
            )
        );

        if ($trace === false) {
            $trace = $prevTrace;
            $trace['class'] = '<script>';
            unset($trace['type']);
            unset($trace['function']);
            unset($trace['object']);
        }

        return [
            isset($trace['object'])
                ? get_class($trace['object'])
                : ($trace['class'] ?? ''),
            $trace['type'] ?? '',
            $trace['function'] ?? '',
            $trace['file'] ?? '',
            $trace['line'] ?? '',
            $trace['args'] ?? [],
            $context
        ];
    }

    private function gatherInvokedFunctionParams($class, $func, $args, $context)
    {
        if (!$func) {
            return '';
        }

        $refl =
            $class
            ? new ReflectionMethod($class, $func)
            : new ReflectionFunction($func)
        ;

        $params = $refl->getParameters();
        $params = array_map(
            function (ReflectionParameter $p, $arg) use ($context) {
                $s = '';

                if ($type = $p->getType()) {
                    if ($type->allowsNull()) {
                        $s .= '?';
                    }
                    $s .= $type->getName() . ' ';
                }

                if ($p->isVariadic()) {
                    $s .= '...';
                }

                $s .= '$' . $p->getName();

                if ($p->isDefaultValueAvailable()) {
                    $val = $p->getDefaultValue();
                    $s .= ' = ' . $context::stringify($val);
                }

                if ($p->isOptional()) {
                    $s = "[$s]";
                }

                $arg = $context::stringify($arg);
                $s .= " { $arg }";

                return $s;
            },
            $params,
            $args
        );
        if ($params) {
            $params = join(",\n    ", $params);
            $params = "(\n    $params\n)";
        } else {
            $params = '()';
        }

        if ($refl->hasReturnType()) {
            $params .= ': ' . $refl->getReturnType()->getName();
        }

        return $params;
    }
}
