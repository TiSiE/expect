<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);

namespace Tisie\Expect\Expectations;

use ReflectionClass;
use Tisie\Expect\InternalExpect;

use function array_filter;
use function array_keys;
use function array_map;
use function in_array;
use function join;
use function strpos;

use const ARRAY_FILTER_USE_KEY;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait ClassConstantsTrait
{
    public function constant($class, string $prefix = '')
    {
        InternalExpect::arg($class, 'class')->is('string|object');

        $class = new ReflectionClass($class);
        $constants = array_filter(
            $class->getConstants(),
            function ($k) use ($prefix) {
                return !$prefix || strpos($k, $prefix) === 0;
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->assert(
            in_array($this->value, $constants, /* strict */ true),
            [$class, $constants]
        );
    }

    protected function constantFail($class, $constants)
    {
        $constants = array_map(
            function ($name, $value) use ($class) {
                return $class->getName() . '::' . $name . "\t ( " . static::stringify($value) . ' )';
            },
            array_keys($constants),
            $constants
        );

        return [
            "%namval% must be one of \n\n * %s",
            'vars' => [join("\n * ", $constants)],
            'throw' => 'domain',
        ];
    }
}
