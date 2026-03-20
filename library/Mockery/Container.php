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

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_shift;
use function array_values;
use function class_exists;
use Closure;
use function count;
use Exception as PHPException;
use function explode;
use function get_class;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function md5;
use Mockery;
use Mockery\Exception\Invalid_Order_Exception;
use Mockery\Exception\RuntimeException;
use Mockery\Generator\Generator;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\Mock_Configuration_Builder;
use Mockery\Loader\Loader as LoaderInterface;
use function preg_grep;
use function preg_match;
use function range;
use ReflectionClass;
use function reset;
use function rtrim;
use function sprintf;
use stdClass;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use Throwable;
use function trait_exists;
use function trim;
/**
 * Container for mock objects
 *
 * @template TMockObject of object
 */
class Container
{
    public const BLOCKS = Mockery::BLOCKS;
    /**
     * Order number of allocation
     *
     * @var int
     */
    protected $_allocated_order = 0;
    /**
     * Current ordered number
     *
     * @var int
     */
    protected $_current_order = 0;
    /**
     * @var Generator
     */
    protected $_generator;
    /**
     * Ordered groups
     *
     * @var array<string,int>
     */
    protected $_groups = [];
    /**
     * @var LoaderInterface
     */
    protected $_loader;
    /**
     * Store of mock objects
     *
     * @template TMockObject of object
     * @var array<class-string<(LegacyMockInterface&TMockObject)|(MockInterface&TMockObject)>,(LegacyMockInterface&TMockObject)|(MockInterface&TMockObject)>
     */
    protected $_mocks = [];
    /**
     * @var array<string,string>
     */
    protected $_named_mocks = [];
    /**
     * @var Instantiator
     */
    protected $instantiator;
    public function __construct(?Generator $generator = null, ?Loader_Interface $loader = null, ?Instantiator $instantiator = null)
    {
        $this->_generator = $generator instanceof Generator ? $generator : Mockery::get_default_generator();
        $this->_loader = $loader instanceof Loader_Interface ? $loader : Mockery::get_default_loader();
        $this->instantiator = $instantiator instanceof Instantiator ? $instantiator : new Instantiator();
    }
    /**
     * Return a specific remembered mock according to the array index it
     * was stored to in this container instance
     *
     * @template TFetchMock of object
     *
     * @param class-string<TFetchMock> $reference
     *
     * @return null|(LegacyMockInterface&TFetchMock)|(MockInterface&TFetchMock)
     */
    public function fetch_mock(string $reference)
    {
        return $this->_mocks[$reference] ?? null;
    }
    /**
     * @return Generator
     */
    public function get_generator()
    {
        return $this->_generator;
    }
    /**
     * @param string $parent
     * @return null|string
     */
    public function get_key_of_demeter_mock_for(string $method, $parent)
    {
        $keys = array_keys($this->_mocks);
        $match = preg_grep('/__demeter_' . md5($parent) . sprintf('_%s$/', $method), $keys);
        if ($match === false) {
            return null;
        }
        if ($match === []) {
            return null;
        }
        return array_values($match)[0];
    }
    /**
     * @return LoaderInterface
     */
    public function get_loader()
    {
        return $this->_loader;
    }
    /**
     * @return array<class-string<(LegacyMockInterface&TMockObject)|(MockInterface&TMockObject)>,(LegacyMockInterface&TMockObject)|(MockInterface&TMockObject)>
     */
    public function get_mocks()
    {
        return $this->_mocks;
    }
    /**
     * @return void
     */
    public function instance_mock()
    {
    }
    /**
     * see http://php.net/manual/en/language.oop5.basic.php
     *
     * @param string $className
     *
     * @return bool
     */
    public function is_valid_class_name($class_name)
    {
        if (trim($class_name) === '') {
            return false;
        }
        if ($class_name[0] === '\\') {
            $class_name = substr($class_name, 1);
            // remove the first backslash
        }
        // all the namespaces and class name should match the regex
        return array_filter(explode('\\', $class_name), static function ($name): bool {
            return !preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name);
        }) === [];
    }
    /**
     * Generates a new mock object for this container
     *
     * I apologies in advance for this. A God Method just fits the API which
     * doesn't require differentiating between classes, interfaces, abstracts,
     * names or partials - just so long as it's something that can be mocked.
     * I'll refactor it one day so it's easier to follow.
     *
     * @template TMock of object
     *
     * @param (array<class-string<TMock>|MockConfigurationBuilder|TMock>|class-string<TMock>|(Closure((LegacyMockInterface&TMock)|(MockInterface&TMock)):void)|TMock) ...$args
     *
     * @throws Throwable
     *
     * @return (LegacyMockInterface&TMock)|(MockInterface&TMock)
     */
    public function mock(...$args)
    {
        [$expectation_closure, $builder, $partial_methods, $quick_definitions, $constructor_args, $blocks] = $this->parse_arguments($args);
        $builder->add_black_listed_methods($blocks);
        if ($constructor_args !== null) {
            $builder->add_black_listed_method('__construct');
        } else {
            $builder->set_mock_original_destructor(true);
        }
        if ($partial_methods !== null && $constructor_args === null) {
            $constructor_args = [];
        }
        $mock_configuration = $builder->get_mock_configuration();
        $this->check_for_named_mock_clashes($mock_configuration);
        $class_name = $this->generate_mock($mock_configuration);
        $mock = $this->initialize_mock($class_name, $constructor_args, $mock_configuration);
        if ($quick_definitions !== []) {
            if (Mockery::get_configuration()->get_quick_definitions()->should_be_called_at_least_once()) {
                $mock->should_receive($quick_definitions)->at_least()->once();
            } else {
                $mock->should_receive($quick_definitions)->by_default();
            }
        }
        if ($expectation_closure instanceof Closure) {
            $expectation_closure($mock);
        }
        return $this->remember_mock($mock);
    }
    /**
     * Fetch the next available allocation order number
     *
     * @return int
     */
    public function mockery_allocate_order()
    {
        return ++$this->_allocated_order;
    }
    /**
     * Reset the container to its original state
     */
    public function mockery_close(): void
    {
        foreach ($this->_mocks as $mock) {
            $mock->mockery_teardown();
        }
        $this->_mocks = [];
    }
    /**
     * Get current ordered number
     *
     * @return int
     */
    public function mockery_get_current_order()
    {
        return $this->_current_order;
    }
    /**
     * Gets the count of expectations on the mocks
     *
     * @return int
     */
    public function mockery_get_expectation_count()
    {
        $count = 0;
        foreach ($this->_mocks as $mock) {
            $count += $mock->mockery_get_expectation_count();
        }
        return $count;
    }
    /**
     * Fetch array of ordered groups
     *
     * @return array<string,int>
     */
    public function mockery_get_groups()
    {
        return $this->_groups;
    }
    /**
     * Set current ordered number
     *
     * @param int $order
     *
     * @return int The current order number that was set
     */
    public function mockery_set_current_order($order)
    {
        return $this->_current_order = $order;
    }
    /**
     * Set ordering for a group
     *
     * @param int    $order
     *
     */
    public function mockery_set_group(string $group, $order): void
    {
        $this->_groups[$group] = $order;
    }
    /**
     * Tear down tasks for this container
     *
     * @throws PHPException
     */
    public function mockery_teardown(): void
    {
        try {
            $this->mockery_verify();
        } catch (Php_Exception $php_exception) {
            $this->mockery_close();
            throw $php_exception;
        }
    }
    /**
     * Retrieves all exceptions thrown by mocks
     *
     * @return array<Throwable>
     */
    public function mockery_thrown_exceptions()
    {
        /** @var array<Throwable> $exceptions */
        $exceptions = [];
        foreach ($this->_mocks as $mock) {
            foreach ($mock->mockery_thrown_exceptions() as $exception) {
                $exceptions[] = $exception;
            }
        }
        return $exceptions;
    }
    /**
     * Validate the current mock's ordering
     *
     * @param int    $order
     *
     * @throws Exception
     *
     */
    public function mockery_validate_order(string $method, $order, Legacy_Mock_Interface $mock): void
    {
        if ($order < $this->_current_order) {
            $exception = new Invalid_Order_Exception(sprintf('Method %s called out of order: expected order %d, was %d', $method, $order, $this->_current_order));
            $exception->set_mock($mock)->set_method_name($method)->set_expected_order($order)->set_actual_order($this->_current_order);
            throw $exception;
        }
        $this->mockery_set_current_order($order);
    }
    /**
     * Verify the container mocks
     */
    public function mockery_verify(): void
    {
        foreach ($this->_mocks as $mock) {
            $mock->mockery_verify();
        }
    }
    /**
     * Store a mock and set its container reference
     *
     * @template TRememberMock
     *
     * @param (LegacyMockInterface&TRememberMock)|(MockInterface&TRememberMock) $mock
     *
     * @return (LegacyMockInterface&TRememberMock)|(MockInterface&TRememberMock)
     */
    public function remember_mock(Legacy_Mock_Interface $mock)
    {
        $class = get_class($mock);
        if (!array_key_exists($class, $this->_mocks)) {
            return $this->_mocks[$class] = $mock;
        }
        /**
         * This condition triggers for an instance mock
         * where origin mock is already remembered
         */
        return $this->_mocks[] = $mock;
    }
    /**
     * Retrieve the last remembered mock object,
     * which is the same as saying retrieve the current mock being programmed where you have yet to call mock()
     * to change it thus why the method name is "self" since it will be used during the programming of the same mock.
     *
     * @return (LegacyMockInterface&TMockObject)|(MockInterface&TMockObject)
     */
    public function self()
    {
        $mocks = array_values($this->_mocks);
        $index = count($mocks) - 1;
        return $mocks[$index];
    }
    /**
     * @template TMock of object
     * @template TMixed
     *
     * @param class-string<TMock> $mockName
     * @param null|array<TMixed>  $constructorArgs
     *
     * @throws Throwable
     *
     * @return TMock
     */
    protected function _get_instance(string $mock_name, $constructor_args = null)
    {
        if ($constructor_args !== null) {
            return (new ReflectionClass($mock_name))->new_instance_args($constructor_args);
        }
        try {
            $instance = $this->instantiator->instantiate($mock_name);
        } catch (Php_Exception $php_exception) {
            /** @var class-string<TMock> $internalMockName */
            $internal_mock_name = $mock_name . '_Internal';
            if (!class_exists($internal_mock_name)) {
                eval(sprintf('class %s extends %s { public function __construct() {} }', $internal_mock_name, $mock_name));
            }
            $instance = new $internal_mock_name();
        }
        return $instance;
    }
    /**
     * @param MockConfiguration $config
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function check_for_named_mock_clashes($config)
    {
        $name = $config->get_name();
        if ($name === null) {
            return;
        }
        $hash = $config->get_hash();
        if (array_key_exists($name, $this->_named_mocks) && $hash !== $this->_named_mocks[$name]) {
            throw new Exception(sprintf("The mock named '%s' has been already defined with a different mock configuration", $name));
        }
        $this->_named_mocks[$name] = $hash;
    }
    private function create_builder(array &$arguments): Mock_Configuration_Builder
    {
        foreach ($arguments as $key => $argument) {
            if (!$argument instanceof Mock_Configuration_Builder) {
                continue;
            }
            unset($arguments[$key]);
            return $argument;
        }
        return new Mock_Configuration_Builder();
    }
    /**
     * @template TMock of object
     *
     * @throws Throwable
     * @return class-string<TMock>
     *
     */
    private function generate_mock(Mock_Configuration $mock_configuration): string
    {
        $mock_definition = $this->get_generator()->generate($mock_configuration);
        $class_name = $mock_definition->get_class_name();
        if (class_exists($class_name, $attempt_autoload = false)) {
            $rfc = new ReflectionClass($class_name);
            if (!$rfc->implements_interface(Legacy_Mock_Interface::class)) {
                throw new RuntimeException(sprintf('Could not load mock %s, class already exists', $class_name));
            }
        }
        $this->get_loader()->load($mock_definition);
        return $class_name;
    }
    /** @return null|Closure(MockInterface):void */
    private function handle_closure(array &$arguments): ?Closure
    {
        if (count($arguments) < 2) {
            return null;
        }
        $argument = array_pop($arguments);
        if ($argument instanceof Closure) {
            /** @var Closure(MockInterface):void $argument */
            return $argument;
        }
        $arguments[] = $argument;
        return null;
    }
    private function initialize_builder(array &$arguments): Mock_Configuration_Builder
    {
        $configuration = Mockery::get_configuration();
        return $this->create_builder($arguments)->set_parameter_overrides($configuration->get_internal_class_method_param_maps())->set_constants_map($configuration->get_constants_map());
    }
    /**
     * @template TMock of object
     *
     * @param class-string<TMock> $className
     *
     * @return (TMock&MockInterface)|(TMock&LegacyMockInterface)
     */
    private function initialize_mock(string $class_name, ?array $constructor_args, Mock_Configuration $mock_configuration): object
    {
        $mock = $this->_get_instance($class_name, $constructor_args);
        $mock->mockery_init($this, $mock_configuration->get_target_object(), $mock_configuration->is_instance_mock());
        return $mock;
    }
    private function parse_arguments(array &$arguments): array
    {
        $blocks = [];
        $constructor_args = null;
        $partial_methods = null;
        $quick_definitions = [];
        $expectation_closure = $this->handle_closure($arguments);
        $builder = $this->initialize_builder($arguments);
        while ($arguments !== []) {
            $argument = array_shift($arguments);
            if (is_string($argument)) {
                $this->parse_string_argument($argument, $builder, $partial_methods);
                continue;
            }
            if (is_object($argument)) {
                $builder->add_target($argument);
                continue;
            }
            if (is_array($argument)) {
                $this->parse_array_argument($argument, $quick_definitions, $constructor_args, $blocks);
                continue;
            }
            throw new Exception(sprintf('Unable to parse arguments sent to %s::mock()', get_class($this)));
        }
        return [$expectation_closure, $builder, $partial_methods, $quick_definitions, $constructor_args, $blocks];
    }
    private function parse_array_argument(array $argument, array &$quick_definitions, ?array &$constructor_args, array &$blocks): void
    {
        if ($argument !== [] && array_keys($argument) !== range(0, count($argument) - 1)) {
            if (array_key_exists(self::BLOCKS, $argument)) {
                $blocks = $argument[self::BLOCKS];
            }
            unset($argument[self::BLOCKS]);
            $quick_definitions = $argument;
        } else {
            $constructor_args = $argument;
        }
    }
    private function parse_string_argument(string $arguments, Mock_Configuration_Builder $builder, ?array &$partial_methods): void
    {
        foreach (explode('|', $arguments) as $type) {
            if ($arguments === 'null') {
                continue;
            }
            if (str_contains($type, ',') && !str_contains($type, ']')) {
                $interfaces = explode(',', str_replace(' ', '', $type));
                $builder->add_targets($interfaces);
                continue;
            }
            if (str_starts_with($type, 'alias:')) {
                $builder->add_target(stdClass::class);
                $builder->set_name(substr($type, 6));
                continue;
            }
            if (str_starts_with($type, 'overload:')) {
                $builder->add_target(stdClass::class);
                $builder->set_instance_mock(true);
                $builder->set_name(substr($type, 9));
                continue;
            }
            if (str_ends_with($type, ']')) {
                $parts = explode('[', $type);
                $class = $parts[0];
                if (!class_exists($class, true) && !interface_exists($class, true)) {
                    throw new Exception('Can only create a partial mock from an existing class or interface');
                }
                $builder->add_target($class);
                $partial_methods = array_filter(explode(',', strtolower(rtrim(str_replace(' ', '', $parts[1]), ']'))));
                foreach ($partial_methods as $partial_method) {
                    if ($partial_method[0] === '!') {
                        $builder->add_black_listed_method(substr($partial_method, 1));
                    } else {
                        $builder->add_white_listed_method($partial_method);
                    }
                }
                continue;
            }
            if (class_exists($type, true) || interface_exists($type, true) || trait_exists($type, true)) {
                $builder->add_target($type);
                continue;
            }
            if (!Mockery::get_configuration()->mocking_non_existent_methods_allowed()) {
                throw new Exception(sprintf("Mockery can't find '%s' so can't mock it", $type));
            }
            if (!$this->is_valid_class_name($type)) {
                throw new Exception('Class name contains invalid characters');
            }
            $builder->add_target($type);
            break;
        }
    }
}