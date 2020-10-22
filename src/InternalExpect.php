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

use Tisie\Expect\Exception\InvalidArgumentException;
use Tisie\Expect\Exception\UnexpectedValueException;
use Tisie\Expect\Expectations\ComparisonTrait;

/**
 * TODO: description
 * @internal
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
class InternalExpect extends AbstractExpect
{
    use ComparisonTrait;

    protected static $exceptions = [
        'default' => InvalidArgumentException::class,
        'value' => UnexpectedValueException::class,
    ];

    public function validOption($key, ...$types)
    {
        return $this->forward(
            'is',
            [
                'throw' => 'value',
                'msg' => "Option key '$key' must be %s, but got %value%",
            ],
            ...$types
        );
    }
}
