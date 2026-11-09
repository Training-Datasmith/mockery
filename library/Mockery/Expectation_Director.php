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

use function array_pop;
use function array_unshift;
use function end;
use Mockery;
use Mockery\Exception\No_Matching_Expectation_Exception;
use const PHP_EOL;
class Expectation_Director
{
    /**
     * Stores an array of all default expectations for this mock
     *
     * @var list<ExpectationInterface>
     */
    protected $_defaults = [];
    /**
     * Stores an array of all expectations for this mock
     *
     * @var list<ExpectationInterface>
     */
    protected $_expectations = [];
    /**
     * The expected order of next call
     *
     * @var int
     */
    protected $_expected_order;
    /**
     * Mock object the director is attached to
     *
     * @var LegacyMockInterface|MockInterface
     */
    protected $_mock;
    /**
     * Method name the director is directing
     *
     * @var string
     */
    protected $_name;
    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct($name, Legacy_Mock_Interface $mock)
    {
        $this->_name = $name;
        $this->_mock = $mock;
    }
    /**
     * Add a new expectation to the director
     */
    public function add_expectation(Expectation $expectation): void
    {
        $this->_expectations[] = $expectation;
    }
    /**
     * Handle a method call being directed by this instance
     *
     * @return mixed
     */
    public function call(array $args)
    {
        $expectation = $this->find_expectation($args);
        if ($expectation !== null) {
            return $expectation->verify_call($args);
        }
        $exception = new No_Matching_Expectation_Exception('No matching handler found for ' . $this->_mock->mockery_get_name() . '::' . Mockery::format_args($this->_name, $args) . '. Either the method was unexpected or its arguments matched' . ' no expected argument list for this method' . PHP_EOL . PHP_EOL . Mockery::format_objects($args));
        $exception->set_mock($this->_mock)->set_method_name($this->_name)->set_actual_arguments($args);
        throw $exception;
    }
    /**
     * Attempt to locate an expectation matching the provided args
     *
     * @return mixed
     */
    public function find_expectation(array $args)
    {
        $expectation = null;
        if ($this->_expectations !== []) {
            $expectation = $this->_find_expectation_in($this->_expectations, $args);
        }
        if ($expectation === null && $this->_defaults !== []) {
            return $this->_find_expectation_in($this->_defaults, $args);
        }
        return $expectation;
    }
    /**
     * Return all expectations assigned to this director
     *
     * @return array<ExpectationInterface>
     */
    public function get_default_expectations()
    {
        return $this->_defaults;
    }
    /**
     * Return the number of expectations assigned to this director.
     */
    public function get_expectation_count(): int
    {
        $count = 0;
        $expectations = $this->get_expectations();
        if ($expectations === []) {
            $expectations = $this->get_default_expectations();
        }
        foreach ($expectations as $expectation) {
            if ($expectation->is_call_count_constrained()) {
                ++$count;
            }
        }
        return $count;
    }
    /**
     * Return all expectations assigned to this director
     *
     * @return array<ExpectationInterface>
     */
    public function get_expectations()
    {
        return $this->_expectations;
    }
    /**
     * Make the given expectation a default for all others assuming it was correctly created last
     *
     * @throws Exception
     */
    public function make_expectation_default(Expectation $expectation): void
    {
        if (end($this->_expectations) === $expectation) {
            array_pop($this->_expectations);
            array_unshift($this->_defaults, $expectation);
            return;
        }
        throw new Exception('Cannot turn a previously defined expectation into a default');
    }
    /**
     * Verify all expectations of the director
     *
     * @throws Exception
     */
    public function verify(): void
    {
        if ($this->_expectations !== []) {
            foreach ($this->_expectations as $expectation) {
                $expectation->verify();
            }
            return;
        }
        foreach ($this->_defaults as $expectation) {
            $expectation->verify();
        }
    }
    /**
     * Search current array of expectations for a match
     *
     * @param array<ExpectationInterface> $expectations
     *
     * @return null|ExpectationInterface
     */
    protected function _find_expectation_in(array $expectations, array $args)
    {
        foreach ($expectations as $expectation) {
            if (!$expectation->is_eligible()) {
                continue;
            }
            if (!$expectation->match_args($args)) {
                continue;
            }
            return $expectation;
        }
        foreach ($expectations as $expectation) {
            if ($expectation->match_args($args)) {
                return $expectation;
            }
        }
        return null;
    }
}