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

use InvalidArgumentException as SplException;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
class InvalidArgumentException extends SplException implements ExceptionInterface, ExpectationExceptionInterface
{
    use ExpectationExceptionTrait;
}
