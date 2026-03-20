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

use function array_map;
use function current;
use function implode;
use function reset;
class Composite_Expectation implements Expectation_Interface
{
    /**
     * Stores an array of all expectations for this composite
     *
     * @var array<ExpectationInterface>
     */
    protected $_expectations = [];
    /**
     * Intercept any expectation calls and direct against all expectations
     *
     *
     * @return self
     */
    public function __call(string $method, array $args)
    {
        foreach ($this->_expectations as $expectation) {
            $expectation->{$method}(...$args);
        }
        return $this;
    }
    /**
     * Return the string summary of this composite expectation
     */
    public function __toString(): string
    {
        $parts = array_map(static function (Expectation_Interface $expectation): string {
            return (string) $expectation;
        }, $this->_expectations);
        return '[' . implode(', ', $parts) . ']';
    }
    /**
     * Add an expectation to the composite
     *
     * @param ExpectationInterface|HigherOrderMessage $expectation
     */
    public function add($expectation): void
    {
        $this->_expectations[] = $expectation;
    }
    /**
     * @param mixed ...$args
     */
    public function and_return(...$args)
    {
        return $this->__call(__FUNCTION__, $args);
    }
    /**
     * Set a return value, or sequential queue of return values
     *
     * @param mixed ...$args
     *
     * @return self
     */
    public function and_returns(...$args)
    {
        return $this->and_return(...$args);
    }
    /**
     * Return the parent mock of the first expectation
     *
     * @return LegacyMockInterface&MockInterface
     */
    public function get_mock()
    {
        reset($this->_expectations);
        $first = current($this->_expectations);
        return $first->get_mock();
    }
    /**
     * Return order number of the first expectation
     *
     * @return int
     */
    public function get_order_number()
    {
        reset($this->_expectations);
        $first = current($this->_expectations);
        return $first->get_order_number();
    }
    /**
     * Mockery API alias to getMock
     *
     * @return LegacyMockInterface&MockInterface
     */
    public function mock()
    {
        return $this->get_mock();
    }
    /**
     * Starts a new expectation addition on the first mock which is the primary target outside of a demeter chain
     *
     * @param mixed ...$args
     *
     * @return Expectation
     */
    public function should_not_receive(...$args)
    {
        reset($this->_expectations);
        $first = current($this->_expectations);
        return $first->get_mock()->should_not_receive(...$args);
    }
    /**
     * Starts a new expectation addition on the first mock which is the primary target, outside of a demeter chain
     *
     * @param mixed ...$args
     *
     * @return Expectation
     */
    public function should_receive(...$args)
    {
        reset($this->_expectations);
        $first = current($this->_expectations);
        return $first->get_mock()->should_receive(...$args);
    }
}