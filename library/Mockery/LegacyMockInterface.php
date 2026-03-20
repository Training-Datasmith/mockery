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

use Closure;
use Throwable;
interface Legacy_Mock_Interface
{
    /**
     * In the event shouldReceive() accepting an array of methods/returns
     * this method will switch them from normal expectations to default
     * expectations
     *
     * @return self
     */
    public function by_default();
    /**
     * Set mock to defer unexpected methods to its parent if possible
     *
     * @return self
     */
    public function make_partial();
    /**
     * Fetch the next available allocation order number
     *
     * @return int
     */
    public function mockery_allocate_order();
    /**
     * Find an expectation matching the given method and arguments
     *
     * @template TMixed
     *
     * @param string        $method
     * @param array<TMixed> $args
     *
     * @return null|Expectation
     */
    public function mockery_find_expectation($method, array $args);
    /**
     * Return the container for this mock
     *
     * @return Container
     */
    public function mockery_get_container();
    /**
     * Get current ordered number
     *
     * @return int
     */
    public function mockery_get_current_order();
    /**
     * Gets the count of expectations for this mock
     *
     * @return int
     */
    public function mockery_get_expectation_count();
    /**
     * Return the expectations director for the given method
     *
     * @param string $method
     *
     * @return null|ExpectationDirector
     */
    public function mockery_get_expectations_for($method);
    /**
     * Fetch array of ordered groups
     *
     * @return array<string,int>
     */
    public function mockery_get_groups();
    /**
     * @return string[]
     */
    public function mockery_get_mockable_methods();
    /**
     * @return array
     */
    public function mockery_get_mockable_properties();
    /**
     * Return the name for this mock
     *
     * @return string
     */
    public function mockery_get_name();
    /**
     * Alternative setup method to constructor
     *
     * @param object $partialObject
     *
     * @return void
     */
    public function mockery_init(?Container $container = null, $partial_object = null);
    /**
     * @return bool
     */
    public function mockery_is_anonymous();
    /**
     * Set current ordered number
     *
     * @param int $order
     *
     * @return int
     */
    public function mockery_set_current_order($order);
    /**
     * Return the expectations director for the given method
     *
     * @param string $method
     *
     * @return null|ExpectationDirector
     */
    public function mockery_set_expectations_for($method, Expectation_Director $director);
    /**
     * Set ordering for a group
     *
     * @param string $group
     * @param int    $order
     *
     * @return void
     */
    public function mockery_set_group($group, $order);
    /**
     * Tear down tasks for this mock
     *
     * @return void
     */
    public function mockery_teardown();
    /**
     * Validate the current mock's ordering
     *
     * @param string $method
     * @param int    $order
     *
     * @throws Exception
     *
     * @return void
     */
    public function mockery_validate_order($method, $order);
    /**
     * Iterate across all expectation directors and validate each
     *
     * @throws Throwable
     *
     * @return void
     */
    public function mockery_verify();
    /**
     * Allows additional methods to be mocked that do not explicitly exist on mocked class
     *
     * @param  string $method the method name to be mocked
     * @return self
     */
    public function should_allow_mocking_method($method);
    /**
     * @return self
     */
    public function should_allow_mocking_protected_methods();
    /**
     * Set mock to defer unexpected methods to its parent if possible
     *
     * @deprecated since 1.4.0. Please use makePartial() instead.
     *
     * @return self
     */
    public function should_defer_missing();
    /**
     * @return self
     */
    public function should_have_been_called();
    /**
     * @template TMixed
     * @param string                     $method
     * @param null|array<TMixed>|Closure $args
     *
     * @return self
     */
    public function should_have_received($method, $args = null);
    /**
     * Set mock to ignore unexpected methods and return Undefined class
     *
     * @template TReturnValue
     *
     * @param null|TReturnValue $returnValue the default return value for calls to missing functions on this mock
     *
     * @return self
     */
    public function should_ignore_missing($return_value = null);
    /**
     * @template TMixed
     * @param null|array<TMixed> $args (optional)
     *
     * @return self
     */
    public function should_not_have_been_called(?array $args = null);
    /**
     * @template TMixed
     * @param string                     $method
     * @param null|array<TMixed>|Closure $args
     *
     * @return self
     */
    public function should_not_have_received($method, $args = null);
    /**
     * Shortcut method for setting an expectation that a method should not be called.
     *
     * @param string ...$methodNames one or many methods that are expected not to be called in this mock
     *
     * @return Expectation|ExpectationInterface|HigherOrderMessage
     */
    public function should_not_receive(...$method_names);
    /**
     * Set expected method calls
     *
     * @param string ...$methodNames one or many methods that are expected to be called in this mock
     *
     * @return Expectation|ExpectationInterface|HigherOrderMessage
     */
    public function should_receive(...$method_names);
}