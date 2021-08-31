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

/**
 * TODO: description
 * @internal
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
class InternalExpect extends AbstractExpect
{
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

    public function validOptionKey()
    {
        return $this->is('string')->assert(
            strpos($this->value, '__') !== 0,
            [
                'msg' => 'Invalid option key "%s". Keys must not start with "__".',
                'vars' => [$this->value],
            ]
        );
    }

    public function validOptionKeys()
    {
        $this->is('array', 'traversable');

        $validKeys = ['__internal__', '__forward__'];

        $invalidKeys = array_filter(
            $this->value,
            fn($x) => !in_array($x, $validKeys) && strpos($x, '__') === 0,
            ARRAY_FILTER_USE_KEY
        );
        //var_dump($this->value, $invalidKeys); exit;
        return $this->assert(
            empty($invalidKeys),
            [$invalidKeys]
        );
    }

    protected function validOptionKeysFail($invalidKeys)
    {
        return 'Invalid options keys used: ' . join(', ', array_keys($invalidKeys));
    }
}
