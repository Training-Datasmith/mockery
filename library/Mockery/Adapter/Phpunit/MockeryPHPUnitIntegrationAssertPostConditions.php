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

trait Mockery_Php_Unit_Integration_Assert_Post_Conditions
{
    protected function assert_post_conditions(): void
    {
        $this->mockery_assert_post_conditions();
    }
}