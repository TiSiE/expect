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

use function preg_match;
use function strlen;
use function strpos;
use function strrev;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
trait StringTrait
{
    public function strlen(int $length)
    {
        return $this->checkStrlen($length);
    }

    public function minlen(int $length)
    {
        return $this->checkStrlen($length, 'min');
    }

    public function maxlen(int $length)
    {
        return $this->checkStrlen($length, 'max');
    }

    private function checkStrlen($length, $mode = 'len')
    {
        $this->is('string');

        switch ($mode) {
            default:
                $cond = strlen($this->value) == $length;
                $msg = 'be exactly';
                break;

            case 'max':
                $cond = strlen($this->value) <= $length;
                $msg = 'not be more than';
                break;

            case 'min':
                $cond = strlen($this->value) >= $length;
                $msg = 'be at least';
                break;
        }

        return $this->assert(
            $cond,
            [
                'msg'   => "%namval% must $msg %s characters long",
                'throw' => 'domain'
            ]
        );
    }

    public function startWithString(string $str)
    {
        return $this->is('string')->assert(
            strpos($this->value, $str) === 0,
            [
                '%namval% must start with "%s"',
                'throw' => 'domain',
            ]
        );
    }

    public function endWithString(string $str)
    {
        return $this->is('string')->assert(
            strpos(strrev($this->value), strrev($str)) === 0,
            [
                '%namval% must end with "%s"',
                'throw' => 'domain',
            ]
        );
    }

    public function regex(string $pattern)
    {
        return $this->is('string')->assert(
            preg_match($pattern, $this->value) > 0,
            [
                '%namval% must match regular expression "%s"',
                'throw' => 'domain',
            ]
        );
    }
}
