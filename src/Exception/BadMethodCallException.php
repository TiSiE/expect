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

use BadMethodCallException as SplBadMethodCallException;

/**
 * TODO: description
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * TODO: write tests
 */
class BadMethodCallException extends SplBadMethodCallException implements ExceptionInterface
{
    use ExpectationExceptionTrait;
}
