<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);

namespace Tisie\Expect;

use InvalidArgumentException;
use Throwable;

use function array_key_exists;
use function array_map;
use function array_merge;
use function array_replace;
use function class_exists;
use function count;
use function debug_backtrace;
use function explode;
use function fclose;
use function fgets;
use function fopen;
use function get_class;
use function get_resource_type;
use function gettype;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function join;
use function method_exists;
use function next;
use function preg_match;
use function preg_replace;
use function rtrim;
use function spl_object_id;
use function str_replace;
use function strpos;
use function trim;
use function ucfirst;
use function vsprintf;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
abstract class AbstractExpect
{
    protected const DEFAULT_EXCEPTION = InvalidArgumentException::class;

    /**
     * Exception aliases map
     *
     * ```
     * [ 'alias' => 'exceptionClass' ]
     * ```
     *
     * The exception class under the key 'default' will be used also,
     * if a non existent exception type or class is getting used.
     * This allows to override DEFAULT_EXCEPTION
     *
     * @var array
     */
    protected static $exceptions = [];

    /**
     * The value of which is something expected
     *
     * @var mixed
     */
    protected $value;

    /**
     * Display name of the value
     *
     * Only used to prettify the exception message
     *
     * If an array, holds the file and line number where
     * the ::arg() method was called from (ideally)
     *
     * @var array|string|null
     */
    protected $name;

    /**
     * Global options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Specification options
     *
     * ```
     * [
     *     'expectationName' => [...]
     * ]
     * ```
     *
     * @var array
     */
    protected $overrides = [];

    /**
     * Create an instance with a value
     *
     * @param mixed $value
     * @param string|null $name Optional name (only used in messages)
     *
     * @return static
     */
    public static function val($value, ?string $name = null)
    {
        return new static($value, $name);
    }

    /**
     * Alias for {@link var()}
     *
     * This is here for syntactic sugar to differ
     * semantically between a variable and a function
     * argument.
     *
     * @see var()
     *
     * @param mixed $value
     * @param string|null $name prepended with '$' if not empty
     *
     * @return static
     */
    public static function arg($value, ?string $name = null)
    {
        return static::withResolveName($value, $name);
    }

    /**
     * Create an instance from a variable.
     *
     * If _$name_ is given, it is prepended with an '$'.
     *
     * If _$name_ is not given, the name of the argument will
     * be parsed from the file this function was called from
     * (but only if an expectation fails).
     *
     * @param mixed $value
     * @param string|null $name
     *
     * @return static
     */
    public static function var($value, ?string $name = null)
    {
        return static::withResolveName($value, $name);
    }

    protected static function withResolveName($value, ?string $name = null)
    {
        if ($name === null) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $name = [$trace[1]['file'], $trace[1]['line']];
        } else {
            $name = "\$$name";
        }

        return new static($value, $name);
    }

    /**
     * Get a string representation for type and value of a variable
     *
     * The representation strings are:
     *
     * * __string__:   '<string> "{actual value}"'
     * * __int__:      '<int> {value}'
     * * __float__:    '<float> {int}.{decimals}' ('.' is decimal delimiter)
     * * __bool__:     '<bool> {true|false}'
     * * __array__:    '<array> [{count}]'
     * * __resource__: '<resource> {resource type}'
     * * __object__:    '<object> {FQCN} (#{SPL object id})'
     *
     * @param mixed $value
     */
    public static function stringify($value): string
    {
        switch ($type = gettype($value)) {
            case 'NULL':
            case 'unknown type':
                $value = null;
                break;

            case 'array':
                $value = '[' . count($value) . ']';
                break;

            case 'object':
                $value = get_class($value) . ' (#' . spl_object_id($value) . ')';
                break;

            case 'resource':
                $value = get_resource_type($value);
                break;

            case 'boolean':
                $value = $value ? 'true' : 'false';
                $type = 'bool';
                break;

            case 'double':
                $type = 'float';
                break;

            case 'integer':
                $type = 'int';
                break;

            case "string":
                $value = "\"$value\"";
                break;

            default:
                break;
        }

        return trim("<$type> $value");
    }

    /**
     * Direct instantiation is not allowed
     *
     * Use the factory methods
     * {@link val()},
     * {@link var()} or
     * {@link arg()}
     *
     * @param mixed $value
     * @param string|null $name
     */
    protected function __construct($value, $name = null)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function set($keyOrOptions, $options = null)
    {
        if ($options === null) {
            $options = $this->parseOptions($keyOrOptions);
            InternalExpect::val($options)->validOptionKeys();
            $this->options = $options;
        } else {
            InternalExpect::val($keyOrOptions)->validOptionKey();
            $this->options[$keyOrOptions] = $options;
        }

        return $this;
    }

    public function for(string $expectation, $keyOrOptions, $options = null)
    {
        if ($options === null) {
            $options = $this->parseOptions($keyOrOptions);
            InternalExpect::val($options)->validOptionKeys();
            $this->overrides[$expectation] = $options;
        } else {
            InternalExpect::val($keyOrOptions)->validOptionKey();
            $this->overrides[$expectation][$keyOrOptions] = $options;
        }

        return $this;
    }

    public function nullable(): object
    {
        if ($this->value === null) {
            return new class
            {
                public function __call($m, $a)
                {
                    return $this;
                }

                public function end(): bool
                {
                    return true;
                }
            };
        }

        return $this;
    }

    /**
     * Always returns _true_
     *
     * If the expectations must return a boolean value
     * you can use this function at the end of the chain.
     *
     * @return true
     */
    public function end(): bool
    {
        return true;
    }

    /**
     * Get the stringified value
     */
    public function strval(): string
    {
        return static::stringify($this->value);
    }

    /**
     * Get the value
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Get the name
     */
    public function name(): ?string
    {
        if (is_array($this->name)) {
            [$file, $line] = $this->name;

            $fp = fopen($file, 'r');
            for ($i = 1, $c = $line; $i < $c; $i++) {
                fgets($fp);
            }
            $lineContent = fgets($fp);
            preg_match('~::\s*(?:arg|var)\s*\(\s*(\$[a-z0-9_]+)~is', $lineContent, $match);
            $this->name = $match[1] ?? null;
            fclose($fp);
        }

        return $this->name;
    }

    /*
     * Built-in expectations
     */

    public function is(string $type, string ...$types)
    {
        return $this->assert(
            $this->checkType($type) || $this->checkType($types)
        );
    }

    protected function checkType($type, $mode = 'union')
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                if ($this->checkType($t)) {
                    if ($mode == 'union') {
                        return true;
                    }

                    continue;
                }

                if ($mode == 'intersect') {
                    return false;
                }
            }

            return $mode == 'intersect';
        }

        if (strpos($type, '|') !== false) {
            return $this->checkType(explode('|', $type));
        }

        if (strpos($type, '&') !== false) {
            return $this->checkType(explode('&', $type), 'intersect');
        }

        return
            (is_callable($cb = "is_$type") && $cb($this->value))
            || $this->value instanceof $type
        ;
    }

    protected function isFail(...$types)
    {
        return [
            '%name% must be %s{{, but got %value%}}',
            'vars' => ['<' . rtrim(join('|', $types), '|') . '>'],
        ];
    }

    public function callback(callable $callback, ...$args)
    {
        return $this->assert(
            ($result = $callback($this->value, ...$args)) === true,
            is_array($result) || is_string($result) ? $result : null
        );
    }

    public function condition(bool $condition, $options = null)
    {
        if (!$condition && is_callable($options)) {
            $options = $options();
        }

        return $this->assert($condition, $options);
    }

    /*
     * Expectation handling
     */

    protected function forward(string $expectation, array $options, ...$args)
    {
        $this->options['__forward__'] = $options;
        $this->$expectation(...$args);
        unset($this->options['__forward__']);

        return $this;
    }

    protected function internal(string $expectation, array $options, ...$args): object
    {
        $this->options['__internal__'] = $options;
        $this->$expectation(...$args);
        unset($this->options['__internal__']);

        return $this;
    }

    protected function assert(bool $condition, $options = null, ?string $failMethod = null)
    {
        return $condition ? $this : $this->fail($options, $failMethod);
    }

    protected function fail($options = null, ?string $failMethod = null)
    {
        [$method, $args, $nested, $forward] = $this->gatherBacktrace();
        $options = $this->gatherOptions($options, $failMethod, $method, $args, $nested, $forward);
        $exception = $this->gatherException($options, $method, $args);
        $message = $this->gatherMessage($options, $method, $args);

        throw new $exception($message, $options['code']);
    }

    protected function gatherBacktrace()
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 7);
        $fncs = ['fail', 'assert'];

        do {
            $trace = next($traces);
        } while (in_array($trace['function'], $fncs));

        /* If the next object is this instance, we have a nested cal stack */
        $next = next($traces);
        $nested = isset($next['object']) && $next['object'] instanceof $this;

        /* If there was a forward, we need the original function name,
         * so that the correct options can be merged later.
         * $next above should be at the "forward" call, so the next
         * step in the trace must be the original method called.
         */
        $next = next($traces);
        $forward =
            isset($this->options['__forward__']) && isset($next['function'])
            ? $next['function']
            : null
        ;

        if ($trace['function'] == 'callback') {
            return ['callback', $trace['args'][1] ?? [], $nested];
        }

        if ($trace['function'] == '__call') {
            return [$trace['args'][0], $trace['args'][1], $nested];
        }

        return [$trace['function'], $trace['args'], $nested, $forward];
    }

    protected function gatherOptions($options, $failMethod, $method, $args, $nested, $forward)
    {
        if (
            $failMethod
            || (is_string($options) && method_exists($this, $failMethod = $options))
            || method_exists($this, $failMethod = "{$method}Fail")
        ) {
            if (!is_array($options)) {
                $options = !$options || $failMethod === $options ? $args : (array) $options;
            }
            $options = $this->$failMethod(...$options);
        }

        $default = [
            'msg' => '%namval% does not meet expectation "' . $method . '"',
            'vars' => null,
            'replace' => null,
            'append' => null,
            'stringify' => false,
            'throw' => 'default',
            'code' => 0
        ];

        if ($forward) {
            $method = $forward;
        }

        $options = $this->parseOptions($options);
        $globals = $this->options ? $this->parseOptions($this->options, $nested) : [];
        $overrides = isset($this->overrides[$method]) ? $this->parseOptions($this->overrides[$method]) : [];
        $forward = isset($this->options['__forward__']) ? $this->parseOptions($this->options['__forward__']) : [];
        $internal = isset($this->options['__internal__']) ? $this->parseOptions($this->options['__internal__']) : [];

        return array_merge(
            $default,
            $options,
            $forward,
            $internal,
            $globals,
            $overrides
        );
    }

    protected function parseOptions($options, $nested = false)
    {
        if (is_string($options) || is_callable($options)) {
            return $nested ? [] : ['msg' => $options];
        }

        if (!is_array($options)) {
            return [];
        }

        if (isset($options[0])) {
            $options['msg'] = $options[0];
            unset($options[0]);
        }

        if ($nested) {
            unset($options['msg']);
            unset($options['vars']);
            unset($options['append']);
            unset($options['replace']);
            unset($options['stringify']);
        }

        return $options;
    }

    protected function gatherException($options, $method, $args)
    {
        $ex = $options['throw'];

        if (is_callable($ex)) {
            $ex = $this->executeCallback($ex, $options, $method, $args);

            if ($ex instanceof Throwable) {
                throw $ex;
            }
        }

        if (!class_exists($ex)) {
            $ex =
                static::$exceptions[$ex]
                ?? static::$exceptions['default']
                ?? static::DEFAULT_EXCEPTION
            ;
        }

        return $ex;
    }

    protected function gatherMessage($options, $method, $args)
    {
        if (is_callable($options['msg'])) {
            $result = $this->executeCallback($options['msg'], $options, $method, $args);

            if (is_string($result)) {
                return $result;
            }

            $options = array_merge($options, $this->parseOptions($result));
        }

        [
            'msg' => $message,
            'vars' => $vars,
            'replace' => $replace,
            'append' => $append,
            'stringify' => $stringify,
        ] = $options;

        InternalExpect::val($message)->validOption('msg', 'string');

        if ($replace) {
            $vars = array_replace($args, (array) $replace);
        } elseif ($append) {
            $vars = array_merge($args, (array) $append);
        } elseif ($vars === null) {
            $vars = $args;
        } elseif ($vars !== false) {
            $vars = (array) $vars;
        } else {
            $vars = [];
        }

        if ($stringify === true) {
            $vars = array_map('static::stringify', $vars);
        } elseif ($stringify !== false) {
            foreach ((array) $stringify as $idx) {
                array_key_exists($idx, $vars) && $vars[$idx] = static::stringify($vars[$idx]);
            }
        }

        $message = str_replace('%namval%', '%name%{{ { %value% }}}', $message);

        if ($name = $this->name()) {
            $message = str_replace(['%name%', '{{', '}}'], [$name, ''], $message);
            $message = preg_replace('~%name\|[^%]+%~is', $name, $message);
        } else {
            $message = preg_replace('~\{\{.*?\}\}(?!\})~s', '', $message);
            $message = preg_replace('~%name\|([^%]+)%~is', '$1', $message);
            $message = str_replace('%name%', '%value%', $message);
        }

        $message = str_replace('%value%', static::stringify($this->value), $message);
        $message = vsprintf($message, $vars);
        $message = ucfirst($message);

        return $message;
    }

    protected function executeCallback($cb, $opts, $method, $args)
    {
        $opts['method'] = $method;
        $opts['args'] = $args;
        $opts['context'] = $this;

        return $cb($opts);
    }
}
