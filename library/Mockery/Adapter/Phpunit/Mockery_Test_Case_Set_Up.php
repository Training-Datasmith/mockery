<?php

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
declare (strict_types=1);
namespace Mockery\Adapter\Phpunit;

trait Mockery_Test_Case_Set_Up
{
    protected function set_up(): void
    {
        parent::set_up();
        $this->mockery_test_set_up();
    }
    protected function tear_down(): void
    {
        $this->mockery_test_tear_down();
        parent::tear_down();
    }
}