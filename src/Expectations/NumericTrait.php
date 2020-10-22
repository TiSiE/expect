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

use function sprintf;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait NumericTrait
{

    public function gt(float $limit)
    {
        return $this->compare($limit, 'gt');
    }

    public function gte(float $limit)
    {
        return $this->compare($limit, 'gte');
    }

    public function lt(float $limit)
    {
        return $this->compare($limit, 'lt');
    }

    public function lte(float $limit)
    {
        return $this->compare($limit, 'lte');
    }

    public function eq(float $value)
    {
        return $this->compare($value, 'eq');
    }

    public function ne(float $value)
    {
        return $this->compare($value, 'ne');
    }

    protected function compare($limit, $mode)
    {
        $this->is('numeric');

        switch ($mode) {
            case 'gt':
                $check = $this->value > $limit;
                $msg = 'greater than';
                break;

            case 'gte':
                $check = $this->value >= $limit;
                $msg = 'greater than or equal to';
                break;

            case 'lt':
                $check = $this->value < $limit;
                $msg = 'lower than';
                break;

            case 'lte':
                $check = $this->value <= $limit;
                $msg = 'lower than or equal to';
                break;

            case 'eq':
            default:
                $check = $this->value == $limit;
                $msg = 'equal to';
                break;

            case 'ne':
                $check = $this->value != $limit;
                $msg = 'not equal to';
        }

        return $this->assert(
            $check,
            [
                "%namval% must be $msg %s",
                'throw' => 'domain',
            ]
        );
    }

    public function interval(float $min, float $max, bool $minInc = true, ?bool $maxInc = null)
    {
        $maxInc = $maxInc ?? $minInc;
        $in =
            ($minInc ? $this->value >= $min : $this->value > $min)
            && ($maxInc ? $this->value <= $max : $this->value < $max)
        ;
        return $this->is('numeric')->assert($in, [$minInc, $maxInc]);
    }

    protected function intervalFail($minInc, $maxInc, $not = false)
    {
        return sprintf(
            '%%namval%% must %sbe in the interval %s%%s, %%s%s',
            $not ? 'not ' : '',
            $minInc ? '[' : ']',
            $maxInc ? ']' : '['
        );
    }

    public function notInterval(float $min, float $max, bool $minInc = true, ?bool $maxInc = null)
    {
        $maxInc = $maxInc ?? $minInc;
        $out =
            ($minInc ? $this->value < $min : $this->value <= $min)
            || ($maxInc ? $this->value > $max : $this->value >= $max)
        ;
        return $this->is('numeric')->assert($out, [$minInc, $maxInc, true], 'intervalFail');
    }
}
