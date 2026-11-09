<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Count_Validator;

use Mockery\Exception\Mockery_Exception_Interface;
use OutOfBoundsException;
class Exception extends OutOfBoundsException implements Mockery_Exception_Interface
{
}