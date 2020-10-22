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

use function get_class;
use function is_a;
use function is_object;
use function sprintf;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait ComparisonTrait
{
    public function equals($value)
    {
        return $this->assert(
            $this->value == $value,
            [
                '%namval% must be equal to %s',
                'stringify' => true
            ]
        );
    }

    public function notEquals($value)
    {
        return $this->assert(
            $this->value != $value,
            [
                '%namval% must not be equal to %s',
                'stringify' => true
            ]
        );
    }

    public function same($value)
    {
        return $this->assert(
            $this->value === $value,
            [
                '%namval% must be identical to %s',
                'stringify' => true
            ]
        );
    }

    public function notSame($value)
    {
        return $this->assert(
            $this->value !== $value,
            [
                '%namval% must not be identical to %s',
                'stringify' => true
            ]
        );
    }

    public function empty()
    {
        return $this->assert(empty($this->value), '%namval% must be empty');
    }

    public function notEmpty()
    {
        return $this->assert(!empty($this->value), '%namval% must not be empty');
    }

    public function null()
    {
        return $this->assert($this->value === null, '%name|Value% must be null');
    }

    public function notNull()
    {
        return $this->assert($this->value !== null, '%name|Value% must not be null');
    }

    public function instance($fqcnOrObject, bool $allowString = false)
    {
        return $this->checkInstance($fqcnOrObject, $allowString);
    }

    public function notInstance($fqcnOrObject, bool $allowString = false)
    {
        return $this->checkInstance($fqcnOrObject, $allowString, true);
    }

    protected function checkInstance($fqcnOrObject, $allowString, $not = false)
    {
        static::arg($fqcnOrObject)->is('string', 'object');
        $this->is('string', 'object');
        $check = is_a($this->value, $fqcnOrObject, $allowString);

        return $this->assert(
            $not ? !$check : $check,
            [$fqcnOrObject, $not, $allowString],
            'instanceFail'
        );
    }

    protected function instanceFail($fqcn, $not = false, $allowString = false)
    {
        return [
            sprintf(
                '%%name%% must %sbe an instance of%s %s{{, but is %%value%%}}',
                $not ? 'not ' : '',
                $allowString ? ' or the class name of a class extending' : '',
                is_object($fqcn) ? get_class($fqcn) : $fqcn
            ),
        ];
    }
}
