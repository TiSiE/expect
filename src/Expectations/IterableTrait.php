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

use Traversable;

use function array_intersect;
use function array_intersect_assoc;
use function array_key_exists;
use function array_map;
use function array_values;
use function in_array;
use function iterator_to_array;
use function join;

use const PHP_EOL;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait IterableTrait
{
    public function oneOf(iterable $choices)
    {
        return $this->oneOfCheck($choices);
    }

    public function notOneOf(iterable $choices)
    {
        return $this->oneOfCheck($choices, true);
    }

    /**
     * @param array|\Traversable $choices
     */
    protected function oneOfCheck(iterable $choices, $not = false)
    {
        $check = in_array($this->value, $this->ensureArray($choices));

        return $this->assert($not ?  !$check : $check, 'oneOfFail');
    }

    protected function oneOfFail($choices, $not = false)
    {
        return [
            "%namval% must %sbe one of \n\n - %s",
            'vars' => [
                $not ? 'not ' : '',
                join("\n - ", array_map('static::stringify', $choices))
            ]
        ];
    }

    public function subset(iterable $set, bool $assoc = false)
    {
        $this->is('iterable');
        $set = $this->ensureArray($set);
        $val = $this->ensureArray($this->value);
        $fnc = $assoc ? 'array_diff_assoc' : 'array_diff';
        $diff = $fnc($val, $set);
        $check = empty($diff);

        return $this->assert(
            $check,
            [$val, $set, $diff, $assoc]
        );
    }

    protected function subsetFail($val, $set, $diff, $assoc = false)
    {
        $fnc = $assoc ? 'array_intersect_assoc' : 'array_intersect';
        $intersect = $fnc($val, $set);

        $result = [];

        foreach ($set as $k => $v) {
            if ($assoc) {
                $result[] =
                    (
                        array_key_exists($k, $intersect) && $intersect[$k] === $v
                        ? '+ '
                        : '  '
                    )
                    . "[$k] => " . static::stringify($v)
                ;
            } else {
                $result[] = (in_array($v, $intersect) ? '+ ' : '  ') . static::stringify($v);
            }
        }

        $result[] = PHP_EOL . 'Not part of the set:';

        foreach ($diff as $k => $v) {
            $result[] =  '- ' . ($assoc ? "[$k] => " : '') .  static::stringify($v);
        }

        return [
            "%name|array% is not a subset of: \n\n%s",
            'vars' => [join(PHP_EOL, $result)],
            'throw' => 'domain',
        ];
    }

    public function superset(iterable $set, bool $assoc = false)
    {
        $this->is('iterable');
        $val = $this->ensureArray($this->value);
        $set = $this->ensureArray($set);
        $arr =
            $assoc
            ? array_intersect_assoc($val, $set)
            : array_values(array_intersect($val, $set))
        ;

        return $this->assert(
            $arr === $set,
            [$set, $arr, $assoc]
        );
    }

    protected function supersetFail($set, $intersect, $assoc = false)
    {
        $result = [];

        foreach ($set as $k => $v) {
            if ($assoc) {
                $result[] =
                    (
                        array_key_exists($k, $intersect) && $intersect[$k] === $v
                        ? '+ '
                        : '- '
                    )
                    . "[$k] => " . static::stringify($v)
                ;
            } else {
                $result[] = (in_array($v, $intersect) ? '+ ' : '- ') . static::stringify($v);
            }
        }

        return [
            "%name|array% is not a superset of:\n\n%s",
            'vars' => [join(PHP_EOL, $result)],
            'throw' => 'domain'
        ];
    }

    protected function ensureArray($iteratorOrArray): array
    {
        return
            $iteratorOrArray instanceof Traversable
            ? iterator_to_array($iteratorOrArray)
            : $iteratorOrArray
        ;
    }
}
