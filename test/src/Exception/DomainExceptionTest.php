<?php

/**
 * [TiSiE] Expect
 *
 * @filesource
 * @copyright 2020 Mathias Gelhausen
 * @license MIT
 */

declare(strict_types=1);

namespace Tisie\ExpectTest\Exception;

use Cross\TestUtils\TestCase\TestInheritanceTrait;
use Cross\TestUtils\TestCase\TestUsesTraitsTrait;
use PHPUnit\Framework\TestCase;
use Tisie\Expect\Exception\DomainException;
use Tisie\Expect\Exception\ExceptionInterface;
use Tisie\Expect\Exception\ExpectationExceptionTrait;

/**
 * Testcase for \Tisie\Expect\Exception\DomainException
 *
 * @covers \Tisie\Expect\Exception\DomainException
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @group Tisie.Expect
 * @group Tisie.Expect.Exception
 */
class DomainExceptionTest extends TestCase
{
    use TestInheritanceTrait, TestUsesTraitsTrait;

    private $target = DomainException::class;

    private $inheritance = [\DomainException::class, ExceptionInterface::class];

    private $usesTraits = [ExpectationExceptionTrait::class];
}
