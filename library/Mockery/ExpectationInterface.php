<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery;

interface Expectation_Interface
{
    /**
     * @template TArgs
     *
     * @param TArgs ...$args
     *
     * @return self
     */
    public function and_return(...$args);
    /**
     * @return self
     */
    public function and_returns();
    /**
     * @return LegacyMockInterface|MockInterface
     */
    public function get_mock();
    /**
     * @return int
     */
    public function get_order_number();
}