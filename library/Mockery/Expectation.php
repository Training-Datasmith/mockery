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

use function array_key_exists;
use function array_search;
use function array_shift;
use function array_slice;
use Closure;
use function count;
use function current;
use const E_USER_DEPRECATED;
use function func_get_args;
use function get_class;
use Hamcrest\Matcher;
use Hamcrest_Matcher;
use function in_array;
use InvalidArgumentException;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use Mockery;
use Mockery\Count_Validator\At_Least;
use Mockery\Count_Validator\At_Most;
use Mockery\Count_Validator\Exact;
use Mockery\Matcher\And_Any_Other_Args;
use Mockery\Matcher\Any_Args;
use Mockery\Matcher\Argument_List_Matcher;
use Mockery\Matcher\Matcher_Interface;
use Mockery\Matcher\Multi_Argument_Closure;
use Mockery\Matcher\No_Args;
use OutOfBoundsException;
use Php_Unit\Framework\Constraint\Constraint;
use Reflection_Exception;
use ReflectionMethod;
use function sprintf;
use Throwable;
use function trigger_error;
class Expectation implements Expectation_Interface
{
    public const ERROR_ZERO_INVOCATION = 'shouldNotReceive(), never(), times(0) chaining additional invocation count methods has been deprecated and will throw an exception in a future version of Mockery';
    /**
     * Actual count of calls to this expectation
     *
     * @var int
     */
    protected $_actual_count = 0;
    /**
     * Exception message
     *
     * @var null|string
     */
    protected $_because;
    /**
     * Array of closures executed with given arguments to generate a result
     * to be returned
     *
     * @var array
     */
    protected $_closure_queue = [];
    /**
     * The count validator class to use
     *
     * @var string
     */
    protected $_count_validator_class = Exact::class;
    /**
     * Count validator store
     *
     * @var array
     */
    protected $_count_validators = [];
    /**
     * Arguments expected by this expectation
     *
     * @var array
     */
    protected $_expected_args = [];
    /**
     * Expected count of calls to this expectation
     *
     * @var int
     */
    protected $_expected_count = -1;
    /**
     * Flag indicating whether the order of calling is determined locally or
     * globally
     *
     * @var bool
     */
    protected $_globally = false;
    /**
     * Integer representing the call order of this expectation on a global basis
     *
     * @var int
     */
    protected $_global_order_number;
    /**
     * Mock object to which this expectation belongs
     *
     * @var LegacyMockInterface
     */
    protected $_mock;
    /**
     * Method name
     *
     * @var string
     */
    protected $_name;
    /**
     * Integer representing the call order of this expectation
     *
     * @var int
     */
    protected $_order_number;
    /**
     * Flag indicating if the return value should be obtained from the original
     * class method instead of returning predefined values from the return queue
     *
     * @var bool
     */
    protected $_passthru = false;
    /**
     * Array of return values as a queue for multiple return sequence
     *
     * @var array
     */
    protected $_return_queue = [];
    /**
     * Value to return from this expectation
     *
     * @var mixed
     */
    protected $_return_value;
    /**
     * Array of values to be set when this expectation matches
     *
     * @var array
     */
    protected $_set_queue = [];
    /**
     * Flag indicating that an exception is expected to be throw (not returned)
     *
     * @var bool
     */
    protected $_throw = false;
    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct(Legacy_Mock_Interface $mock, $name)
    {
        $this->_mock = $mock;
        $this->_name = $name;
        $this->with_any_args();
    }
    /**
     * Cloning logic
     */
    public function __clone()
    {
        $new_validators = [];
        $count_validators = $this->_count_validators;
        foreach ($count_validators as $validator) {
            $new_validators[] = clone $validator;
        }
        $this->_count_validators = $new_validators;
    }
    /**
     * Return a string with the method name and arguments formatted
     */
    public function __toString(): string
    {
        return Mockery::format_args($this->_name, $this->_expected_args);
    }
    /**
     * Set a return value, or sequential queue of return values
     *
     * @param mixed ...$args
     */
    public function and_return(...$args): self
    {
        $this->_return_queue = $args;
        return $this;
    }
    /**
     * Sets up a closure to return the nth argument from the expected method call
     *
     * @param int $index
     */
    public function and_return_arg($index): self
    {
        if (!is_int($index) || $index < 0) {
            throw new InvalidArgumentException('Invalid argument index supplied. Index must be a non-negative integer.');
        }
        $closure = static function (...$args) use ($index) {
            if (array_key_exists($index, $args)) {
                return $args[$index];
            }
            throw new OutOfBoundsException('Cannot return an argument value. No argument exists for the index ' . $index);
        };
        $this->_closure_queue = [$closure];
        return $this;
    }
    /**
     * @return self
     */
    public function and_return_false()
    {
        return $this->and_return(false);
    }
    /**
     * Return null. This is merely a language construct for Mock describing.
     *
     * @return self
     */
    public function and_return_null()
    {
        return $this->and_return(null);
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
     * Return this mock, like a fluent interface
     *
     * @return self
     */
    public function and_return_self()
    {
        return $this->and_return($this->_mock);
    }
    /**
     * @return self
     */
    public function and_return_true()
    {
        return $this->and_return(true);
    }
    /**
     * Return a self-returning black hole object.
     *
     * @return self
     */
    public function and_return_undefined()
    {
        return $this->and_return(new Undefined());
    }
    /**
     * Set a closure or sequence of closures with which to generate return
     * values. The arguments passed to the expected method are passed to the
     * closures as parameters.
     *
     * @param callable ...$args
     */
    public function and_return_using(...$args): self
    {
        $this->_closure_queue = $args;
        return $this;
    }
    /**
     * Set a sequential queue of return values with an array
     *
     * @return self
     */
    public function and_return_values(array $values)
    {
        return $this->and_return(...$values);
    }
    /**
     * Register values to be set to a public property each time this expectation occurs
     *
     * @param string $name
     * @param array  ...$values
     */
    public function and_set($name, ...$values): self
    {
        $this->_set_queue[$name] = $values;
        return $this;
    }
    /**
     * Set Exception class and arguments to that class to be thrown
     *
     * @param string|Throwable $exception
     * @param string           $message
     * @param int              $code
     *
     * @return self
     */
    public function and_throw($exception, $message = '', $code = 0, ?\Exception $previous = null)
    {
        $this->_throw = true;
        if (is_object($exception)) {
            return $this->and_return($exception);
        }
        return $this->and_return(new $exception($message, $code, $previous));
    }
    /**
     * Set Exception classes to be thrown
     *
     * @return self
     */
    public function and_throw_exceptions(array $exceptions)
    {
        $this->_throw = true;
        foreach ($exceptions as $exception) {
            if (!is_object($exception)) {
                throw new Exception('You must pass an array of exception objects to andThrowExceptions');
            }
        }
        return $this->and_return_values($exceptions);
    }
    public function and_throws($exception, $message = '', $code = 0, ?\Exception $previous = null)
    {
        return $this->and_throw($exception, $message, $code, $previous);
    }
    /**
     * Sets up a closure that will yield each of the provided args
     *
     * @param mixed ...$args
     */
    public function and_yield(...$args): self
    {
        $closure = static function () use ($args) {
            foreach ($args as $arg) {
                yield $arg;
            }
        };
        $this->_closure_queue = [$closure];
        return $this;
    }
    /**
     * Sets next count validator to the AtLeast instance
     */
    public function at_least(): self
    {
        $this->_count_validator_class = At_Least::class;
        return $this;
    }
    /**
     * Sets next count validator to the AtMost instance
     */
    public function at_most(): self
    {
        $this->_count_validator_class = At_Most::class;
        return $this;
    }
    /**
     * Set the exception message
     *
     * @param string $message
     *
     * @return $this
     */
    public function because($message): self
    {
        $this->_because = $message;
        return $this;
    }
    /**
     * Shorthand for setting minimum and maximum constraints on call counts
     *
     * @param int $minimum
     * @param int $maximum
     */
    public function between($minimum, $maximum)
    {
        return $this->at_least()->times($minimum)->at_most()->times($maximum);
    }
    /**
     * Mark this expectation as being a default
     */
    public function by_default(): self
    {
        $director = $this->_mock->mockery_get_expectations_for($this->_name);
        if ($director instanceof Expectation_Director) {
            $director->make_expectation_default($this);
        }
        return $this;
    }
    /**
     * @return null|string
     */
    public function get_exception_message()
    {
        return $this->_because;
    }
    /**
     * Return the parent mock of the expectation
     *
     * @return LegacyMockInterface|MockInterface
     */
    public function get_mock()
    {
        return $this->_mock;
    }
    public function get_name()
    {
        return $this->_name;
    }
    /**
     * Return order number
     *
     * @return int
     */
    public function get_order_number()
    {
        return $this->_order_number;
    }
    /**
     * Indicates call order should apply globally
     */
    public function globally(): self
    {
        $this->_globally = true;
        return $this;
    }
    /**
     * Check if there is a constraint on call count
     */
    public function is_call_count_constrained(): bool
    {
        return $this->_count_validators !== [];
    }
    /**
     * Checks if this expectation is eligible for additional calls
     */
    public function is_eligible(): bool
    {
        foreach ($this->_count_validators as $validator) {
            if (!$validator->is_eligible($this->_actual_count)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Check if passed arguments match an argument expectation
     *
     * @return bool
     */
    public function match_args(array $args)
    {
        foreach ($this->_expected_args as $expected_arg) {
            if (!$expected_arg instanceof Argument_List_Matcher) {
                continue;
            }
            return $this->_match_arg($expected_arg, $args);
        }
        $arg_count = count($args);
        $expected_args_count = count($this->_expected_args);
        if ($arg_count === $expected_args_count) {
            return $this->_match_args($args);
        }
        $last_expected_argument = $this->_expected_args[$expected_args_count - 1];
        if ($last_expected_argument instanceof And_Any_Other_Args) {
            $first_corresponding_key = array_search($last_expected_argument, $this->_expected_args, true);
            $args = array_slice($args, 0, $first_corresponding_key);
            return $this->_match_args($args);
        }
        return false;
    }
    /**
     * Indicates that this expectation is never expected to be called
     *
     * @return self
     */
    public function never()
    {
        return $this->times(0);
    }
    /**
     * Indicates that this expectation is expected exactly once
     *
     * @return self
     */
    public function once()
    {
        return $this->times(1);
    }
    /**
     * Indicates that this expectation must be called in a specific given order
     *
     * @param string $group Name of the ordered group
     */
    public function ordered($group = null): self
    {
        if ($this->_globally) {
            $this->_global_order_number = $this->_define_ordered($group, $this->_mock->mockery_get_container());
        } else {
            $this->_order_number = $this->_define_ordered($group, $this->_mock);
        }
        $this->_globally = false;
        return $this;
    }
    /**
     * Flag this expectation as calling the original class method with
     * the provided arguments instead of using a return value queue.
     */
    public function passthru(): self
    {
        if ($this->_mock instanceof Mock) {
            throw new Exception('Mock Objects not created from a loaded/existing class are incapable of passing method calls through to a parent class');
        }
        $this->_passthru = true;
        return $this;
    }
    /**
     * Alias to andSet(). Allows the natural English construct
     * - set('foo', 'bar')->andReturn('bar')
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function set($name, $value)
    {
        return $this->and_set(...func_get_args());
    }
    /**
     * Indicates the number of times this expectation should occur
     *
     * @param int $limit
     *
     * @throws InvalidArgumentException
     */
    public function times($limit = null): self
    {
        if ($limit === null) {
            return $this;
        }
        if (!is_int($limit)) {
            throw new InvalidArgumentException('The passed Times limit should be an integer value');
        }
        if ($this->_expected_count === 0) {
            @trigger_error(self::ERROR_ZERO_INVOCATION, E_USER_DEPRECATED);
            // throw new \InvalidArgumentException(self::ERROR_ZERO_INVOCATION);
        }
        if ($limit === 0) {
            $this->_count_validators = [];
        }
        $this->_expected_count = $limit;
        $this->_count_validators[$this->_count_validator_class] = new $this->_count_validator_class($this, $limit);
        if ($this->_count_validator_class !== Exact::class) {
            $this->_count_validator_class = Exact::class;
            unset($this->_count_validators[$this->_count_validator_class]);
        }
        return $this;
    }
    /**
     * Indicates that this expectation is expected exactly twice
     *
     * @return self
     */
    public function twice()
    {
        return $this->times(2);
    }
    /**
     * Verify call order
     */
    public function validate_order(): void
    {
        if ($this->_order_number) {
            $this->_mock->mockery_validate_order((string) $this, $this->_order_number, $this->_mock);
        }
        if ($this->_global_order_number) {
            $this->_mock->mockery_get_container()->mockery_validate_order((string) $this, $this->_global_order_number, $this->_mock);
        }
    }
    /**
     * Verify this expectation
     */
    public function verify(): void
    {
        foreach ($this->_count_validators as $validator) {
            $validator->validate($this->_actual_count);
        }
    }
    /**
     * Verify the current call, i.e. that the given arguments match those
     * of this expectation
     *
     * @throws Throwable
     *
     * @return mixed
     */
    public function verify_call(array $args)
    {
        $this->validate_order();
        ++$this->_actual_count;
        if ($this->_passthru === true) {
            return $this->_mock->mockery_call_subject_method($this->_name, $args);
        }
        $return = $this->_get_return_value($args);
        $this->throw_as_necessary($return);
        $this->_set_values();
        return $return;
    }
    /**
     * Expected argument setter for the expectation
     *
     * @param mixed ...$args
     *
     * @return self
     */
    public function with(...$args)
    {
        return $this->with_args($args);
    }
    /**
     * Set expectation that the given arguments are acceptable plus variable other arguments
     *
     * @param mixed ...$args
     *
     * @return self
     */
    public function with_and_others(...$args)
    {
        return $this->with_args(array_merge($args, [new And_Any_Other_Args()]));
    }
    /**
     * Set expectation that any arguments are acceptable
     */
    public function with_any_args(): self
    {
        $this->_expected_args = [new Any_Args()];
        return $this;
    }
    /**
     * Expected arguments for the expectation passed as an array or a closure that matches each passed argument on
     * each function call.
     *
     * @param array|Closure $argsOrClosure
     *
     * @return self
     */
    public function with_args($args_or_closure)
    {
        if (is_array($args_or_closure)) {
            return $this->with_args_in_array($args_or_closure);
        }
        if ($args_or_closure instanceof Closure) {
            return $this->with_args_matched_by_closure($args_or_closure);
        }
        throw new InvalidArgumentException(sprintf('Call to %s with an invalid argument (%s), only array and closure are allowed', __METHOD__, $args_or_closure));
    }
    /**
     * Set with() as no arguments expected
     */
    public function with_no_args(): self
    {
        $this->_expected_args = [new No_Args()];
        return $this;
    }
    /**
     * Expected arguments should partially match the real arguments
     *
     * @param mixed ...$expectedArgs
     *
     * @return self
     */
    public function with_some_of_args(...$expected_args)
    {
        return $this->with_args(static function (...$args) use ($expected_args): bool {
            foreach ($expected_args as $expected_arg) {
                if (!in_array($expected_arg, $args, true)) {
                    return false;
                }
            }
            return true;
        });
    }
    /**
     * Indicates this expectation should occur zero or more times
     *
     * @return self
     */
    public function zero_or_more_times()
    {
        return $this->at_least()->never();
    }
    /**
     * Setup the ordering tracking on the mock or mock container
     *
     * @param string $group
     * @param object $ordering
     *
     * @return int
     */
    protected function _define_ordered($group, $ordering)
    {
        $groups = $ordering->mockery_get_groups();
        if ($group === null) {
            return $ordering->mockery_allocate_order();
        }
        if (array_key_exists($group, $groups)) {
            return $groups[$group];
        }
        $result = $ordering->mockery_allocate_order();
        $ordering->mockery_set_group($group, $result);
        return $result;
    }
    /**
     * Fetch the return value for the matching args
     *
     * @return mixed
     */
    protected function _get_return_value(array $args)
    {
        $closure_queue_count = count($this->_closure_queue);
        if ($closure_queue_count > 1) {
            return array_shift($this->_closure_queue)(...$args);
        }
        if ($closure_queue_count > 0) {
            return current($this->_closure_queue)(...$args);
        }
        $return_queue_count = count($this->_return_queue);
        if ($return_queue_count > 1) {
            return array_shift($this->_return_queue);
        }
        if ($return_queue_count > 0) {
            return current($this->_return_queue);
        }
        return $this->_mock->mockery_return_value_for_method($this->_name);
    }
    /**
     * Check if passed argument matches an argument expectation
     *
     * @param mixed $expected
     * @param mixed $actual
     *
     * @return bool
     */
    protected function _match_arg($expected, &$actual)
    {
        if ($expected === $actual) {
            return true;
        }
        if ($expected instanceof Matcher_Interface) {
            return $expected->match($actual);
        }
        if ($expected instanceof Constraint) {
            return (bool) $expected->evaluate($actual, '', true);
        }
        if ($expected instanceof Matcher || $expected instanceof Hamcrest_Matcher) {
            @trigger_error('Hamcrest package has been deprecated and will be removed in 2.0', E_USER_DEPRECATED);
            return $expected->matches($actual);
        }
        if (is_object($expected)) {
            $matcher = Mockery::get_configuration()->get_default_matcher(get_class($expected));
            return $matcher === null ? false : $this->_match_arg(new $matcher($expected), $actual);
        }
        if (is_object($actual) && is_string($expected) && $actual instanceof $expected) {
            return true;
        }
        return $expected == $actual;
    }
    /**
     * Check if the passed arguments match the expectations, one by one.
     *
     *
     */
    protected function _match_args(array $args): bool
    {
        foreach ($this->_expected_args as $index => $expected_arg) {
            $param =& $args[$index];
            if (!$this->_match_arg($expected_arg, $param)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Sets public properties with queued values to the mock object
     *
     * @return void
     */
    protected function _set_values()
    {
        $mock_class = get_class($this->_mock);
        $container = $this->_mock->mockery_get_container();
        $mocks = $container->get_mocks();
        foreach ($this->_set_queue as $name => &$values) {
            if ($values === []) {
                continue;
            }
            $value = array_shift($values);
            $this->_mock->{$name} = $value;
            foreach ($mocks as $mock) {
                if (!$mock instanceof $mock_class) {
                    continue;
                }
                if (!$mock->mockery_is_instance()) {
                    continue;
                }
                $mock->{$name} = $value;
            }
        }
    }
    /**
     * Throws an exception if the expectation has been configured to do so
     *
     * @param Throwable $return
     *
     * @throws Throwable
     */
    private function throw_as_necessary($return): void
    {
        if (!$this->_throw) {
            return;
        }
        if (!$return instanceof Throwable) {
            return;
        }
        throw $return;
    }
    /**
     * Expected arguments for the expectation passed as an array
     *
     * @return self
     */
    private function with_args_in_array(array $arguments)
    {
        if ($arguments === []) {
            return $this->with_no_args();
        }
        $reflection_method = null;
        try {
            $reflection_method = new ReflectionMethod($this->get_mock(), $this->get_name());
        } catch (Reflection_Exception $_) {
        }
        if ($reflection_method instanceof ReflectionMethod && !array_is_list($arguments)) {
            $_arguments = [];
            foreach ($reflection_method->get_parameters() as $reflection_parameter) {
                if ([] === $arguments) {
                    // Avoid over populating argument list
                    break;
                }
                $name = $reflection_parameter->get_name();
                $position = $reflection_parameter->get_position();
                if (array_key_exists($name, $arguments)) {
                    $_arguments[$position] = $arguments[$name];
                    unset($arguments[$name]);
                    continue;
                }
                if ($reflection_parameter->is_default_value_available()) {
                    $_arguments[$position] = $reflection_parameter->get_default_value();
                }
            }
            $arguments = $_arguments;
        }
        $this->_expected_args = $arguments;
        return $this;
    }
    /**
     * Expected arguments have to be matched by the given closure.
     */
    private function with_args_matched_by_closure(Closure $closure): self
    {
        $this->_expected_args = [new Multi_Argument_Closure($closure)];
        return $this;
    }
}