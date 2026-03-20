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

use function method_exists;
use Mockery;
use Php_Unit\Framework\Attributes\After;
use Php_Unit\Framework\Attributes\Before;
/**
 * Integrates Mockery into PHPUnit. Ensures Mockery expectations are verified
 * for each test and are included by the assertion counter.
 */
trait Mockery_Php_Unit_Integration
{
    use Mockery_Php_Unit_Integration_Assert_Post_Conditions;
    protected $mockery_open;
    protected function add_mockery_expectations_to_assertion_count()
    {
        $this->add_to_assertion_count(Mockery::get_container()->mockery_get_expectation_count());
    }
    protected function check_mockery_exceptions()
    {
        if (!method_exists($this, 'markAsRisky')) {
            return;
        }
        foreach (Mockery::get_container()->mockery_thrown_exceptions() as $e) {
            if (!$e->dismissed()) {
                $this->mark_as_risky();
            }
        }
    }
    protected function close_mockery()
    {
        Mockery::close();
        $this->mockery_open = false;
    }
    /**
     * Performs assertions shared by all tests of a test case. This method is
     * called before execution of a test ends and before the tearDown method.
     */
    protected function mockery_assert_post_conditions()
    {
        $this->add_mockery_expectations_to_assertion_count();
        $this->check_mockery_exceptions();
        $this->close_mockery();
        parent::assert_post_conditions();
    }
    /**
     * @after
     */
    #[After]
    protected function purge_mockery_container()
    {
        if ($this->mockery_open) {
            // post conditions wasn't called, so test probably failed
            Mockery::close();
        }
    }
    /**
     * @before
     */
    #[Before]
    protected function start_mockery()
    {
        $this->mockery_open = true;
    }
}