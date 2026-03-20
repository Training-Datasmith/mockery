<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Adapter\Phpunit;

use Php_Unit\Framework\Test_Case;
abstract class Mockery_Test_Case extends Test_Case
{
    use Mockery_Php_Unit_Integration;
    use Mockery_Test_Case_Set_Up;
    /**
     * @return void
     */
    protected function mockery_test_set_up()
    {
    }
    /**
     * @return void
     */
    protected function mockery_test_tear_down()
    {
    }
}