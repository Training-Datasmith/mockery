<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license   https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link      https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery;

use Mockery\Count_Validator\Exception;
use Mockery\Exception\BadMethodCallException;
use Mockery\Exception\Invalid_Order_Exception;
use Mockery\Exception\No_Matching_Expectation_Exception;
#[\Allow_Dynamic_Properties]
class Mock implements Mock_Interface
{
    /**
     * Stores an array of all expectation directors for this mock
     *
     * @var array
     */
    protected $_mockery_expectations = [];
    /**
     * Stores an initial number of expectations that can be manipulated
     * while using the getter method.
     *
     * @var int
     */
    protected $_mockery_expectations_count = 0;
    /**
     * Flag to indicate whether we can ignore method calls missing from our
     * expectations
     *
     * @var bool
     */
    protected $_mockery_ignore_missing = false;
    /**
     * Flag to indicate whether we want to set the ignoreMissing flag on
     * mocks generated form this calls to this one
     *
     * @var bool
     */
    protected $_mockery_ignore_missing_recursive = false;
    /**
     * Flag to indicate whether we can defer method calls missing from our
     * expectations
     *
     * @var bool
     */
    protected $_mockery_defer_missing = false;
    /**
     * Flag to indicate whether this mock was verified
     *
     * @var bool
     */
    protected $_mockery_verified = false;
    /**
     * Given name of the mock
     *
     * @var string
     */
    protected $_mockery_name;
    /**
     * Order number of allocation
     *
     * @var int
     */
    protected $_mockery_allocated_order = 0;
    /**
     * Current ordered number
     *
     * @var int
     */
    protected $_mockery_current_order = 0;
    /**
     * Ordered groups
     *
     * @var array
     */
    protected $_mockery_groups = [];
    /**
     * Mock container containing this mock object
     *
     * @var Container
     */
    protected $_mockery_container;
    /**
     * Instance of a core object on which methods are called in the event
     * it has been set, and an expectation for one of the object's methods
     * does not exist. This implements a simple partial mock proxy system.
     *
     * @var object
     */
    protected $_mockery_partial;
    /**
     * Flag to indicate we should ignore all expectations temporarily. Used
     * mainly to prevent expectation matching when in the middle of a mock
     * object recording session.
     *
     * @var bool
     */
    protected $_mockery_disable_expectation_matching = false;
    /**
     * Stores all stubbed public methods separate from any on-object public
     * properties that may exist.
     *
     * @var array
     */
    protected $_mockery_mockable_properties = [];
    /**
     * @var array
     */
    protected $_mockery_mockable_methods = [];
    /**
     * Just a local cache for this mock's target's methods
     *
     * @var \ReflectionMethod[]
     */
    protected static $_mockery_methods;
    protected $_mockery_allow_mocking_protected_methods = false;
    protected $_mockery_received_method_calls;
    /**
     * If shouldIgnoreMissing is called, this value will be returned on all calls to missing methods
     * @var mixed
     */
    protected $_mockery_default_return_value;
    /**
     * Tracks internally all the bad method call exceptions that happened during runtime
     *
     * @var array
     */
    protected $_mockery_thrown_exceptions = [];
    protected $_mockery_instance_mock = true;
    /** @var null|string $parentClass */
    private $_mockery_parent_class;
    /**
     * We want to avoid constructors since class is copied to Generator.php
     * for inclusion on extending class definitions.
     *
     * @param Container $container
     * @param object $partialObject
     * @param bool $instanceMock
     */
    public function mockery_init(?Container $container = null, $partial_object = null, $instance_mock = true): void
    {
        if (null === $container) {
            $container = new Container();
        }
        $this->_mockery_container = $container;
        if (!is_null($partial_object)) {
            $this->_mockery_partial = $partial_object;
        }
        if (!\Mockery::get_configuration()->mocking_non_existent_methods_allowed()) {
            foreach ($this->mockery_get_methods() as $method) {
                if ($method->is_public()) {
                    $this->_mockery_mockable_methods[] = $method->get_name();
                }
            }
        }
        $this->_mockery_instance_mock = $instance_mock;
        $this->_mockery_parent_class = get_parent_class($this);
    }
    /**
     * Set expected method calls
     *
     * @param string ...$methodNames one or many methods that are expected to be called in this mock
     *
     * @return ExpectationInterface|Expectation|HigherOrderMessage
     */
    public function should_receive(...$method_names)
    {
        if ($method_names === []) {
            return new Higher_Order_Message($this, 'shouldReceive');
        }
        foreach ($method_names as $method) {
            if ('' === $method) {
                throw new \InvalidArgumentException('Received empty method name');
            }
        }
        $self = $this;
        $allow_mocking_protected_methods = $this->_mockery_allow_mocking_protected_methods;
        return \Mockery::parse_should_return_args($this, $method_names, static function (string $method) use ($self, $allow_mocking_protected_methods): \Mockery\Expectation {
            $rm = $self->mockery_get_method($method);
            if ($rm) {
                if ($rm->is_private()) {
                    throw new \InvalidArgumentException($method . '() cannot be mocked as it is a private method');
                }
                if (!$allow_mocking_protected_methods && $rm->is_protected()) {
                    throw new \InvalidArgumentException($method . '() cannot be mocked as it is a protected method and mocking protected methods is not enabled for the currently used mock object. Use shouldAllowMockingProtectedMethods() to enable mocking of protected methods.');
                }
            }
            $director = $self->mockery_get_expectations_for($method);
            if (!$director) {
                $director = new Expectation_Director($method, $self);
                $self->mockery_set_expectations_for($method, $director);
            }
            $expectation = new Expectation($self, $method);
            $director->add_expectation($expectation);
            return $expectation;
        });
    }
    // start method allows
    /**
     * @param mixed $something  String method name or map of method => return
     * @return self|ExpectationInterface|Expectation|HigherOrderMessage
     */
    public function allows($something = [])
    {
        if (is_string($something)) {
            return $this->should_receive($something);
        }
        if (empty($something)) {
            return $this->should_receive();
        }
        foreach ($something as $method => $return_value) {
            $this->should_receive($method)->and_return($return_value);
        }
        return $this;
    }
    // end method allows
    // start method expects
    /**
        /**
    * @param mixed $something  String method name (optional)
     * @return ExpectationInterface|Expectation|ExpectsHigherOrderMessage
    */
    public function expects($something = null)
    {
        if (is_string($something)) {
            return $this->should_receive($something)->once();
        }
        return new Expects_Higher_Order_Message($this);
    }
    // end method expects
    /**
     * Shortcut method for setting an expectation that a method should not be called.
     *
     * @param string ...$methodNames one or many methods that are expected not to be called in this mock
     * @return ExpectationInterface|Expectation|HigherOrderMessage
     */
    public function should_not_receive(...$method_names)
    {
        if ($method_names === []) {
            return new Higher_Order_Message($this, 'shouldNotReceive');
        }
        $expectation = call_user_func_array(function (string $method_names) {
            return $this->should_receive($method_names);
        }, $method_names);
        $expectation->never();
        return $expectation;
    }
    /**
     * Allows additional methods to be mocked that do not explicitly exist on mocked class
     *
     * @param string $method name of the method to be mocked
     * @return Mock|MockInterface|LegacyMockInterface
     */
    public function should_allow_mocking_method($method): self
    {
        $this->_mockery_mockable_methods[] = $method;
        return $this;
    }
    /**
     * Set mock to ignore unexpected methods and return Undefined class
     * @param mixed $returnValue the default return value for calls to missing functions on this mock
     * @param bool $recursive Specify if returned mocks should also have shouldIgnoreMissing set
     * @return static
     */
    public function should_ignore_missing($return_value = null, $recursive = false): self
    {
        $this->_mockery_ignore_missing = true;
        $this->_mockery_ignore_missing_recursive = $recursive;
        $this->_mockery_default_return_value = $return_value;
        return $this;
    }
    public function as_undefined(): self
    {
        $this->_mockery_ignore_missing = true;
        $this->_mockery_default_return_value = new Undefined();
        return $this;
    }
    /**
     * @return static
     */
    public function should_allow_mocking_protected_methods(): self
    {
        if (!\Mockery::get_configuration()->mocking_non_existent_methods_allowed()) {
            foreach ($this->mockery_get_methods() as $method) {
                if ($method->is_protected()) {
                    $this->_mockery_mockable_methods[] = $method->get_name();
                }
            }
        }
        $this->_mockery_allow_mocking_protected_methods = true;
        return $this;
    }
    /**
     * Set mock to defer unexpected methods to it's parent
     *
     * This is particularly useless for this class, as it doesn't have a parent,
     * but included for completeness
     *
     * @deprecated 2.0.0 Please use makePartial() instead
     *
     * @return static
     */
    public function should_defer_missing()
    {
        return $this->make_partial();
    }
    /**
     * Set mock to defer unexpected methods to it's parent
     *
     * It was an alias for shouldDeferMissing(), which will be removed
     * in 2.0.0.
     *
     * @return static
     */
    public function make_partial(): self
    {
        $this->_mockery_defer_missing = true;
        return $this;
    }
    /**
     * In the event shouldReceive() accepting one or more methods/returns,
     * this method will switch them from normal expectations to default
     * expectations
     */
    public function by_default(): self
    {
        foreach ($this->_mockery_expectations as $director) {
            $exps = $director->get_expectations();
            foreach ($exps as $exp) {
                $exp->by_default();
            }
        }
        return $this;
    }
    /**
     * Capture calls to this mock
     */
    public function __call(string $method, array $args)
    {
        return $this->_mockery_handle_method_call($method, $args);
    }
    public static function __callStatic(string $method, array $args)
    {
        return self::_mockery_handle_static_method_call($method, $args);
    }
    /**
     * Forward calls to this magic method to the __call method
     */
    #[\Return_Type_Will_Change]
    public function __toString(): string
    {
        return $this->__call('__toString', []);
    }
    /**
     * Iterate across all expectation directors and validate each
     *
     * @throws Exception
     */
    public function mockery_verify(): void
    {
        if ($this->_mockery_verified) {
            return;
        }
        if (property_exists($this, '_mockery_ignoreVerification') && $this->_mockery_ignore_verification !== null && $this->_mockery_ignore_verification == true) {
            return;
        }
        $this->_mockery_verified = true;
        foreach ($this->_mockery_expectations as $director) {
            $director->verify();
        }
    }
    /**
     * Gets a list of exceptions thrown by this mock
     *
     * @return array
     */
    public function mockery_thrown_exceptions()
    {
        return $this->_mockery_thrown_exceptions;
    }
    /**
     * Tear down tasks for this mock
     *
     * @return void
     */
    public function mockery_teardown()
    {
    }
    /**
     * Fetch the next available allocation order number
     *
     * @return int
     */
    public function mockery_allocate_order()
    {
        ++$this->_mockery_allocated_order;
        return $this->_mockery_allocated_order;
    }
    /**
     * Set ordering for a group
     *
     * @param mixed $group
     * @param int $order
     */
    public function mockery_set_group($group, $order): void
    {
        $this->_mockery_groups[$group] = $order;
    }
    /**
     * Fetch array of ordered groups
     *
     * @return array
     */
    public function mockery_get_groups()
    {
        return $this->_mockery_groups;
    }
    /**
     * Set current ordered number
     *
     * @param int $order
     */
    public function mockery_set_current_order($order)
    {
        $this->_mockery_current_order = $order;
        return $this->_mockery_current_order;
    }
    /**
     * Get current ordered number
     *
     * @return int
     */
    public function mockery_get_current_order()
    {
        return $this->_mockery_current_order;
    }
    /**
     * Validate the current mock's ordering
     *
     * @param string $method
     * @param int $order
     * @throws \Mockery\Exception
     */
    public function mockery_validate_order($method, $order): void
    {
        if ($order < $this->_mockery_current_order) {
            $exception = new Invalid_Order_Exception('Method ' . self::class . '::' . $method . '()' . ' called out of order: expected order ' . $order . ', was ' . $this->_mockery_current_order);
            $exception->set_mock($this)->set_method_name($method)->set_expected_order($order)->set_actual_order($this->_mockery_current_order);
            throw $exception;
        }
        $this->mockery_set_current_order($order);
    }
    /**
     * Gets the count of expectations for this mock
     *
     * @return int
     */
    public function mockery_get_expectation_count()
    {
        $count = $this->_mockery_expectations_count;
        foreach ($this->_mockery_expectations as $director) {
            $count += $director->get_expectation_count();
        }
        return $count;
    }
    /**
     * Return the expectations director for the given method
     *
     * @var string $method
     */
    public function mockery_set_expectations_for($method, Expectation_Director $director): void
    {
        $this->_mockery_expectations[$method] = $director;
    }
    /**
     * Return the expectations director for the given method
     *
     * @var string $method
     * @return ExpectationDirector|null
     */
    public function mockery_get_expectations_for($method)
    {
        if (isset($this->_mockery_expectations[$method])) {
            return $this->_mockery_expectations[$method];
        }
    }
    /**
     * Find an expectation matching the given method and arguments
     *
     * @var string $method
     * @var array $args
     * @return Expectation|null
     */
    public function mockery_find_expectation($method, array $args)
    {
        if (!isset($this->_mockery_expectations[$method])) {
            return null;
        }
        $director = $this->_mockery_expectations[$method];
        return $director->find_expectation($args);
    }
    /**
     * Return the container for this mock
     *
     * @return Container
     */
    public function mockery_get_container()
    {
        return $this->_mockery_container;
    }
    /**
     * Return the name for this mock
     */
    public function mockery_get_name(): string
    {
        return self::class;
    }
    /**
     * @return array
     */
    public function mockery_get_mockable_properties()
    {
        return $this->_mockery_mockable_properties;
    }
    public function __isset(string $name)
    {
        if (false !== stripos($name, '_mockery_')) {
            return false;
        }
        if (!$this->_mockery_parent_class) {
            return false;
        }
        if (!method_exists($this->_mockery_parent_class, '__isset')) {
            return false;
        }
        return call_user_func($this->_mockery_parent_class . '::__isset', $name);
    }
    public function mockery_get_expectations()
    {
        return $this->_mockery_expectations;
    }
    /**
     * Calls a parent class method and returns the result. Used in a passthru
     * expectation where a real return value is required while still taking
     * advantage of expectation matching and call count verification.
     *
     * @return mixed
     */
    public function mockery_call_subject_method(string $name, array $args)
    {
        if (!method_exists($this, $name) && $this->_mockery_parent_class && method_exists($this->_mockery_parent_class, '__call')) {
            return call_user_func($this->_mockery_parent_class . '::__call', $name, $args);
        }
        return call_user_func_array($this->_mockery_parent_class . '::' . $name, $args);
    }
    /**
     * @return string[]
     */
    public function mockery_get_mockable_methods()
    {
        return $this->_mockery_mockable_methods;
    }
    public function mockery_is_anonymous(): bool
    {
        $rfc = new \ReflectionClass($this);
        // PHP 8 has Stringable interface
        $interfaces = array_filter($rfc->get_interfaces(), static function (\ReflectionClass $i): bool {
            return $i->get_name() !== 'Stringable';
        });
        return false === $rfc->get_parent_class() && 2 === count($interfaces);
    }
    public function mockery_is_instance()
    {
        return $this->_mockery_instance_mock;
    }
    public function __wakeup()
    {
        /**
         * This does not add __wakeup method support. It's a blind method and any
         * expected __wakeup work will NOT be performed. It merely cuts off
         * annoying errors where a __wakeup exists but is not essential when
         * mocking
         */
    }
    public function __destruct()
    {
        /**
         * Overrides real class destructor in case if class was created without original constructor
         */
    }
    public function mockery_get_method($name)
    {
        foreach ($this->mockery_get_methods() as $method) {
            if ($method->get_name() == $name) {
                return $method;
            }
        }
        return null;
    }
    /**
     * @param string $name Method name.
     *
     * @return mixed Generated return value based on the declared return value of the named method.
     */
    public function mockery_return_value_for_method($name)
    {
        $rm = $this->mockery_get_method($name);
        if ($rm === null) {
            return null;
        }
        $return_type = Reflector::get_simplest_return_type($rm);
        switch ($return_type) {
            case null:
            case 'void':
                return null;
            case 'string':
                return '';
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'bool':
            case 'false':
                return false;
            case 'true':
                return true;
            case 'array':
            case 'iterable':
                return [];
            case 'callable':
            case '\Closure':
                return static function (): void {
                };
            case '\Traversable':
            case '\Generator':
                $generator = static function () {
                    yield;
                };
                return $generator();
            case 'static':
                return $this;
            case 'object':
                $mock = \Mockery::mock();
                if ($this->_mockery_ignore_missing_recursive) {
                    $mock->should_ignore_missing($this->_mockery_default_return_value, true);
                }
                return $mock;
            default:
                $mock = \Mockery::mock($return_type);
                if ($this->_mockery_ignore_missing_recursive) {
                    $mock->should_ignore_missing($this->_mockery_default_return_value, true);
                }
                return $mock;
        }
    }
    public function should_have_received($method = null, $args = null)
    {
        if ($method === null) {
            return new Higher_Order_Message($this, 'shouldHaveReceived');
        }
        $expectation = new Verification_Expectation($this, $method);
        if (null !== $args) {
            $expectation->with_args($args);
        }
        $expectation->at_least()->once();
        $director = new Verification_Director($this->_mockery_get_received_method_calls(), $expectation);
        ++$this->_mockery_expectations_count;
        $director->verify();
        return $director;
    }
    public function should_have_been_called()
    {
        return $this->should_have_received('__invoke');
    }
    public function should_not_have_received($method = null, $args = null): ?\Mockery\Higher_Order_Message
    {
        if ($method === null) {
            return new Higher_Order_Message($this, 'shouldNotHaveReceived');
        }
        $expectation = new Verification_Expectation($this, $method);
        if (null !== $args) {
            $expectation->with_args($args);
        }
        $expectation->never();
        $director = new Verification_Director($this->_mockery_get_received_method_calls(), $expectation);
        ++$this->_mockery_expectations_count;
        $director->verify();
        return null;
    }
    public function should_not_have_been_called(?array $args = null)
    {
        return $this->should_not_have_received('__invoke', $args);
    }
    protected static function _mockery_handle_static_method_call(string $method, array $args)
    {
        $associated_real_object = \Mockery::fetch_mock(self::class);
        try {
            return $associated_real_object->__call($method, $args);
        } catch (BadMethodCallException $bad_method_call_exception) {
            throw new BadMethodCallException('Static method ' . $associated_real_object->mockery_get_name() . '::' . $method . '() does not exist on this mock object', 0, $bad_method_call_exception);
        }
    }
    protected function _mockery_get_received_method_calls()
    {
        return $this->_mockery_received_method_calls ?: $this->_mockery_received_method_calls = new Received_Method_Calls();
    }
    /**
     * Called when an instance Mock was created and its constructor is getting called
     *
     * @see \Mockery\Generator\StringManipulation\Pass\InstanceMockPass
     */
    protected function _mockery_constructor_called(array $args)
    {
        if (!isset($this->_mockery_expectations['__construct'])) {
            return;
        }
        $this->_mockery_handle_method_call('__construct', $args);
    }
    protected function _mockery_find_expected_method_handler($method)
    {
        if (isset($this->_mockery_expectations[$method])) {
            return $this->_mockery_expectations[$method];
        }
        $lower_cased_mockery_expectations = array_change_key_case($this->_mockery_expectations, CASE_LOWER);
        $lower_cased_method = strtolower($method);
        return $lower_cased_mockery_expectations[$lower_cased_method] ?? null;
    }
    protected function _mockery_handle_method_call($method, array $args)
    {
        $this->_mockery_get_received_method_calls()->push(new Method_Call($method, $args));
        $rm = $this->mockery_get_method($method);
        if ($rm && $rm->is_protected() && !$this->_mockery_allow_mocking_protected_methods) {
            if ($rm->is_abstract()) {
                return;
            }
            try {
                $prototype = $rm->get_prototype();
                if ($prototype->is_abstract()) {
                    return;
                }
            } catch (\Reflection_Exception $re) {
                // noop - there is no hasPrototype method
            }
            if (null === $this->_mockery_parent_class) {
                $this->_mockery_parent_class = get_parent_class($this);
            }
            return call_user_func_array($this->_mockery_parent_class . '::' . $method, $args);
        }
        $handler = $this->_mockery_find_expected_method_handler($method);
        if ($handler !== null && !$this->_mockery_disable_expectation_matching) {
            try {
                return $handler->call($args);
            } catch (No_Matching_Expectation_Exception $e) {
                if (!$this->_mockery_ignore_missing && !$this->_mockery_defer_missing) {
                    throw $e;
                }
            }
        }
        if (!is_null($this->_mockery_partial) && (method_exists($this->_mockery_partial, $method) || method_exists($this->_mockery_partial, '__call'))) {
            return $this->_mockery_partial->{$method}(...$args);
        }
        if ($this->_mockery_defer_missing && is_callable($this->_mockery_parent_class . '::' . $method) && (!$this->has_method_overloading_in_parent_class() || $this->_mockery_parent_class && method_exists($this->_mockery_parent_class, $method))) {
            return call_user_func_array($this->_mockery_parent_class . '::' . $method, $args);
        }
        if ($this->_mockery_defer_missing && $this->_mockery_parent_class && method_exists($this->_mockery_parent_class, '__call')) {
            return call_user_func($this->_mockery_parent_class . '::__call', $method, $args);
        }
        if ($method === '__toString') {
            // __toString is special because we force its addition to the class API regardless of the
            // original implementation.  Thus, we should always return a string rather than honor
            // _mockery_ignoreMissing and break the API with an error.
            return sprintf('%s#%s', self::class, spl_object_hash($this));
        }
        if ($this->_mockery_ignore_missing && (\Mockery::get_configuration()->mocking_non_existent_methods_allowed() || !is_null($this->_mockery_partial) && method_exists($this->_mockery_partial, $method) || is_callable($this->_mockery_parent_class . '::' . $method))) {
            if ($this->_mockery_default_return_value instanceof Undefined) {
                return $this->_mockery_default_return_value->{$method}(...$args);
            }
            if (null === $this->_mockery_default_return_value) {
                return $this->mockery_return_value_for_method($method);
            }
            return $this->_mockery_default_return_value;
        }
        $message = 'Method ' . self::class . '::' . $method . '() does not exist on this mock object';
        if (!is_null($rm)) {
            $message = 'Received ' . self::class . '::' . $method . '(), but no expectations were specified';
        }
        $bmce = new BadMethodCallException($message);
        $this->_mockery_thrown_exceptions[] = $bmce;
        throw $bmce;
    }
    /**
     * Uses reflection to get the list of all
     * methods within the current mock object
     *
     * @return array
     */
    protected function mockery_get_methods()
    {
        if (static::$_mockery_methods && \Mockery::get_configuration()->reflection_cache_enabled()) {
            return static::$_mockery_methods;
        }
        if ($this->_mockery_partial !== null) {
            $reflected = new \Reflection_Object($this->_mockery_partial);
        } else {
            $reflected = new \ReflectionClass($this);
        }
        return static::$_mockery_methods = $reflected->get_methods();
    }
    private function has_method_overloading_in_parent_class(): bool
    {
        // if there's __call any name would be callable
        return is_callable($this->_mockery_parent_class . '::aFunctionNameThatNoOneWouldEverUseInRealLife12345');
    }
    private function get_non_public_methods(): array
    {
        return array_map(static function ($method) {
            return $method->get_name();
        }, array_filter($this->mockery_get_methods(), static function ($method): bool {
            return !$method->is_public();
        }));
    }
}