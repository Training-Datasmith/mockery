<?php

declare(strict_types=1);

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

/**
 * Integrates Mockery into PHPUnit. Ensures Mockery expectations are verified
 * for each test and are included by the assertion counter.
 */
trait Mockery_Php_Unit_Integration
{
    use Mockery_Php_Unit_Integration_Assert_Post_Conditions;

    /** @var bool */
    protected $mockery_open;

    /**
     * Performs assertions shared by all tests of a test case. This method is
     * called before execution of a test ends and before the tearDown method.
     */
    protected function mockery_assert_post_conditions(): void
    {
        $this->add_mockery_expectations_to_assertion_count();
        $this->check_mockery_exceptions();
        $this->close_mockery();

        parent::assertPostConditions();
    }

    protected function add_mockery_expectations_to_assertion_count(): void
    {
        $this->addToAssertionCount(Mockery::get_container()->mockery_get_expectation_count());
    }

    protected function check_mockery_exceptions(): void
    {
        if (!is_callable([$this, 'markAsRisky'])) {
            return;
        }

        foreach (Mockery::get_container()->mockery_thrown_exceptions() as $e) {
            if (!$e->dismissed()) {
                $this->markAsRisky();

                return;
            }
        }
    }

    protected function close_mockery(): void
    {
        Mockery::close();
        $this->mockery_open = false;
    }

    /**
     * @before
     */
    protected function start_mockery(): void
    {
        $this->mockery_open = true;
    }

    /**
     * @after
     */
    protected function purge_mockery_container(): void
    {
        if ($this->mockery_open) {
            Mockery::close();
        }
    }
}
