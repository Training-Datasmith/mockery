<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
use Mockery\Closure_Wrapper;
use Mockery\Composite_Expectation;
use Mockery\Configuration;
use Mockery\Container;
use Mockery\Exception as MockeryException;
use Mockery\Expectation_Director;
use Mockery\Expectation_Interface;
use Mockery\Generator\Caching_Generator;
use Mockery\Generator\Generator;
use Mockery\Generator\Mock_Configuration_Builder;
use Mockery\Generator\Mock_Name_Builder;
use Mockery\Generator\String_Manipulation_Generator;
use Mockery\Legacy_Mock_Interface;
use Mockery\Loader\Eval_Loader;
use Mockery\Loader\Loader;
use Mockery\Matcher\And_Any_Other_Args;
use Mockery\Matcher\Any;
use Mockery\Matcher\Any_Of;
use Mockery\Matcher\Closure as ClosureMatcher;
use Mockery\Matcher\Contains;
use Mockery\Matcher\Ducktype;
use Mockery\Matcher\Has_Key;
use Mockery\Matcher\Has_Value;
use Mockery\Matcher\Is_Equal;
use Mockery\Matcher\Is_Same;
use Mockery\Matcher\Matcher_Interface;
use Mockery\Matcher\Must_Be;
use Mockery\Matcher\Not;
use Mockery\Matcher\Not_Any_Of;
use Mockery\Matcher\Pattern;
use Mockery\Matcher\Subset;
use Mockery\Matcher\Type;
use Mockery\Mock_Interface;
use Mockery\Reflector;
class Mockery
{
    public const BLOCKS = 'Mockery_Forward_Blocks';
    /**
     * Global configuration handler containing configuration options.
     *
     * @var Configuration
     */
    protected static $_config;
    /**
     * Global container to hold all mocks for the current unit test running.
     *
     * @var null|Container
     */
    protected static $_container;
    /**
     * @var Generator
     */
    protected static $_generator;
    /**
     * @var Loader
     */
    protected static $_loader;
    /**
     * @var list<string>
     */
    private static $_files_to_clean_up = [];
    /**
     * Return instance of AndAnyOtherArgs matcher.
     */
    public static function and_any_other_args(): \Mockery\Matcher\And_Any_Other_Args
    {
        return new And_Any_Other_Args();
    }
    /**
     * Return instance of AndAnyOtherArgs matcher.
     *
     * An alternative name to `andAnyOtherArgs` so
     * the API stays closer to `any` as well.
     */
    public static function and_any_others(): \Mockery\Matcher\And_Any_Other_Args
    {
        return new And_Any_Other_Args();
    }
    /**
     * Return instance of ANY matcher.
     */
    public static function any(): \Mockery\Matcher\Any
    {
        return new Any();
    }
    /**
     * Return instance of ANYOF matcher.
     *
     * @template TAnyOf
     *
     * @param TAnyOf ...$args
     */
    public static function any_of(...$args): \Mockery\Matcher\Any_Of
    {
        return new Any_Of($args);
    }
    /**
     * @deprecated since 1.3.2 and will be removed in 2.0.
     */
    public static function built_in_types(): array
    {
        return ['array', 'bool', 'callable', 'float', 'int', 'iterable', 'object', 'self', 'string', 'void'];
    }
    /**
     * Return instance of CLOSURE matcher.
     *
     * @template TReference
     *
     * @param TReference $reference
     */
    public static function capture(&$reference): \Mockery\Matcher\Closure
    {
        $closure = static function ($argument) use (&$reference): bool {
            $reference = $argument;
            return true;
        };
        return new Closure_Matcher($closure);
    }
    /**
     * Static shortcut to closing up and verifying all mocks in the global
     * container, and resetting the container static variable to null.
     */
    public static function close(): void
    {
        foreach (self::$_files_to_clean_up as $file_name) {
            @\unlink($file_name);
        }
        self::$_files_to_clean_up = [];
        if (self::$_container === null) {
            return;
        }
        $container = self::$_container;
        self::$_container = null;
        $container->mockery_teardown();
        $container->mockery_close();
    }
    /**
     * Return instance of CONTAINS matcher.
     *
     * @template TContains
     *
     * @param TContains $args
     */
    public static function contains(...$args): \Mockery\Matcher\Contains
    {
        return new Contains($args);
    }
    /**
     * @param class-string $fqn
     */
    public static function declare_class($fqn): void
    {
        static::declare_type($fqn, 'class');
    }
    /**
     * @param class-string $fqn
     */
    public static function declare_interface($fqn): void
    {
        static::declare_type($fqn, 'interface');
    }
    /**
     * Return instance of DUCKTYPE matcher.
     *
     * @template TDucktype
     *
     * @param TDucktype ...$args
     */
    public static function ducktype(...$args): \Mockery\Matcher\Ducktype
    {
        return new Ducktype($args);
    }
    /**
     * Static fetching of a mock associated with a name or explicit class poser.
     *
     * @template TFetchMock of object
     *
     * @param class-string<TFetchMock> $name
     *
     * @return null|(LegacyMockInterface&MockInterface&TFetchMock)
     */
    public static function fetch_mock($name)
    {
        return self::get_container()->fetch_mock($name);
    }
    /**
     * Utility method to format method name and arguments into a string.
     *
     *
     */
    public static function format_args(string $method, ?array $arguments = null): string
    {
        if ($arguments === null) {
            return $method . '()';
        }
        $formatted_arguments = [];
        foreach ($arguments as $argument) {
            $formatted_arguments[] = self::format_argument($argument);
        }
        return $method . '(' . \implode(', ', $formatted_arguments) . ')';
    }
    /**
     * Utility function to format objects to printable arrays.
     */
    public static function format_objects(?array $objects = null): string
    {
        static $formatting;
        if ($formatting) {
            return '[Recursion]';
        }
        if ($objects === null) {
            return '';
        }
        $objects = \array_filter($objects, 'is_object');
        if ($objects === []) {
            return '';
        }
        $formatting = true;
        $parts = [];
        foreach ($objects as $object) {
            $parts[\get_class($object)] = self::object_to_array($object);
        }
        $formatting = false;
        return 'Objects: ( ' . \var_export($parts, true) . ')';
    }
    /**
     * Lazy loader and Getter for the global
     * configuration container.
     *
     * @return Configuration
     */
    public static function get_configuration()
    {
        if (self::$_config === null) {
            self::$_config = new Configuration();
        }
        return self::$_config;
    }
    /**
     * Lazy loader and getter for the container property.
     *
     * @return Container
     */
    public static function get_container()
    {
        if (self::$_container === null) {
            self::$_container = new Container(self::get_generator(), self::get_loader());
        }
        return self::$_container;
    }
    /**
     * Creates and returns a default generator
     * used inside this class.
     */
    public static function get_default_generator(): \Mockery\Generator\Caching_Generator
    {
        return new Caching_Generator(String_Manipulation_Generator::with_default_passes());
    }
    /**
     * Gets an EvalLoader to be used as default.
     */
    public static function get_default_loader(): \Mockery\Loader\Eval_Loader
    {
        return new Eval_Loader();
    }
    /**
     * Lazy loader method and getter for
     * the generator property.
     *
     * @return Generator
     */
    public static function get_generator()
    {
        if (self::$_generator === null) {
            self::$_generator = self::get_default_generator();
        }
        return self::$_generator;
    }
    /**
     * Lazy loader method and getter for
     * the $_loader property.
     *
     * @return Loader
     */
    public static function get_loader()
    {
        if (self::$_loader === null) {
            self::$_loader = self::get_default_loader();
        }
        return self::$_loader;
    }
    /**
     * Defines the global helper functions
     */
    public static function global_helpers(): void
    {
        require_once __DIR__ . '/helpers.php';
    }
    /**
     * Return instance of HASKEY matcher.
     *
     * @template THasKey
     *
     * @param THasKey $key
     */
    public static function has_key($key): \Mockery\Matcher\Has_Key
    {
        return new Has_Key($key);
    }
    /**
     * Return instance of HASVALUE matcher.
     *
     * @template THasValue
     *
     * @param THasValue $val
     */
    public static function has_value($val): \Mockery\Matcher\Has_Value
    {
        return new Has_Value($val);
    }
    /**
     * Static and Semantic shortcut to Container::mock().
     *
     * @template TInstanceMock
     *
     * @param class-string<TInstanceMock>|TInstanceMock|array<mixed> ...$args
     *
     * @return LegacyMockInterface&MockInterface&TInstanceMock
     */
    public static function instance_mock(...$args)
    {
        return self::get_container()->mock(...$args);
    }
    /**
     * @param string $type
     *
     *
     * @deprecated since 1.3.2 and will be removed in 2.0.
     */
    public static function is_built_in_type($type): bool
    {
        return \in_array($type, self::built_in_types(), true);
    }
    /**
     * Return instance of IsEqual matcher.
     *
     * @template TExpected
     *
     * @param TExpected $expected
     */
    public static function is_equal($expected): Is_Equal
    {
        return new Is_Equal($expected);
    }
    /**
     * Return instance of IsSame matcher.
     *
     * @template TExpected
     *
     * @param TExpected $expected
     */
    public static function is_same($expected): Is_Same
    {
        return new Is_Same($expected);
    }
    /**
     * Static shortcut to Container::mock().
     *
     * @template TMock of object
     *
     * @param class-string<TMock>|TMock|Closure(LegacyMockInterface&MockInterface&TMock):LegacyMockInterface&MockInterface&TMock|array<TMock> ...$args
     *
     * @return LegacyMockInterface&MockInterface&TMock
     */
    public static function mock(...$args)
    {
        return self::get_container()->mock(...$args);
    }
    /**
     * Return instance of MUSTBE matcher.
     *
     * @template TExpected
     *
     * @param TExpected $expected
     */
    public static function must_be($expected): \Mockery\Matcher\Must_Be
    {
        return new Must_Be($expected);
    }
    /**
     * Static shortcut to Container::mock(), first argument names the mock.
     *
     * @template TNamedMock
     *
     * @param class-string<TNamedMock>|TNamedMock|array<mixed> ...$args
     *
     * @return LegacyMockInterface&MockInterface&TNamedMock
     */
    public static function named_mock(...$args)
    {
        $name = \array_shift($args);
        $builder = new Mock_Configuration_Builder();
        $builder->set_name($name);
        \array_unshift($args, $builder);
        return self::get_container()->mock(...$args);
    }
    /**
     * Return instance of NOT matcher.
     *
     * @template TNotExpected
     *
     * @param TNotExpected $expected
     */
    public static function not($expected): \Mockery\Matcher\Not
    {
        return new Not($expected);
    }
    /**
     * Return instance of NOTANYOF matcher.
     *
     * @template TNotAnyOf
     *
     * @param TNotAnyOf ...$args
     */
    public static function not_any_of(...$args): \Mockery\Matcher\Not_Any_Of
    {
        return new Not_Any_Of($args);
    }
    /**
     * Return instance of CLOSURE matcher.
     *
     * @template TClosure of Closure
     *
     * @param TClosure $closure
     */
    public static function on($closure): \Mockery\Matcher\Closure
    {
        return new Closure_Matcher($closure);
    }
    /**
     * Utility function to parse shouldReceive() arguments and generate
     * expectations from such as needed.
     *
     * @template TReturnArgs
     *
     * @param TReturnArgs ...$args
     * @param Closure     $add
     */
    public static function parse_should_return_args(Legacy_Mock_Interface $mock, $args, $add): \Mockery\Composite_Expectation
    {
        $composite = new Composite_Expectation();
        foreach ($args as $arg) {
            if (\is_string($arg)) {
                $composite->add(self::build_demeter_chain($mock, $arg, $add));
                continue;
            }
            if (\is_array($arg)) {
                foreach ($arg as $k => $v) {
                    $composite->add(self::build_demeter_chain($mock, $k, $add)->and_return($v));
                }
            }
        }
        return $composite;
    }
    /**
     * Return instance of PATTERN matcher.
     *
     * @template TPatter
     *
     * @param TPatter $expected
     */
    public static function pattern($expected): \Mockery\Matcher\Pattern
    {
        return new Pattern($expected);
    }
    /**
     * Register a file to be deleted on tearDown.
     *
     * @param string $fileName
     */
    public static function register_file_for_clean_up($file_name): void
    {
        self::$_files_to_clean_up[] = $file_name;
    }
    /**
     * Reset the container to null.
     */
    public static function reset_container(): void
    {
        self::$_container = null;
    }
    /**
     * Static shortcut to Container::self().
     *
     * @throws LogicException
     *
     * @return LegacyMockInterface|MockInterface
     */
    public static function self()
    {
        if (self::$_container === null) {
            throw new LogicException('You have not declared any mocks yet');
        }
        return self::$_container->self();
    }
    /**
     * Set the container.
     *
     * @return Container
     */
    public static function set_container(Container $container)
    {
        return self::$_container = $container;
    }
    /**
     * Setter for the $_generator static property.
     */
    public static function set_generator(Generator $generator): void
    {
        self::$_generator = $generator;
    }
    /**
     * Setter for the $_loader static property.
     */
    public static function set_loader(Loader $loader): void
    {
        self::$_loader = $loader;
    }
    /**
     * Static and semantic shortcut for getting a mock from the container
     * and applying the spy's expected behavior into it.
     *
     * @template TSpy
     *
     * @param class-string<TSpy>|TSpy|Closure(LegacyMockInterface&MockInterface&TSpy):LegacyMockInterface&MockInterface&TSpy|array<TSpy> ...$args
     *
     * @return (LegacyMockInterface&TSpy)|(MockInterface&TSpy)
     */
    public static function spy(...$args)
    {
        if ($args !== [] && $args[0] instanceof Closure) {
            $args[0] = new Closure_Wrapper($args[0]);
        }
        return self::get_container()->mock(...$args)->should_ignore_missing();
    }
    /**
     * Return instance of SUBSET matcher.
     *
     * @param bool $strict - (Optional) True for strict comparison, false for loose
     */
    public static function subset(array $part, $strict = true): \Mockery\Matcher\Subset
    {
        return new Subset($part, $strict);
    }
    /**
     * Return instance of TYPE matcher.
     *
     * @template TExpectedType
     *
     * @param TExpectedType $expected
     */
    public static function type($expected): \Mockery\Matcher\Type
    {
        return new Type($expected);
    }
    /**
     * Sets up expectations on the members of the CompositeExpectation and
     * builds up any demeter chain that was passed to shouldReceive.
     *
     * @param string  $arg
     * @param Closure $add
     *
     * @throws MockeryException
     *
     * @return ExpectationInterface
     */
    protected static function build_demeter_chain(Legacy_Mock_Interface $mock, $arg, $add)
    {
        $container = $mock->mockery_get_container();
        $method_names = \explode('->', $arg);
        if (!$mock->mockery_is_anonymous() && !self::get_configuration()->mocking_non_existent_methods_allowed() && !\in_array(\current($method_names), $mock->mockery_get_mockable_methods(), true)) {
            throw new Mockery_Exception("Mockery's configuration currently forbids mocking the method " . \current($method_names) . ' as it does not exist on the class or object ' . 'being mocked');
        }
        $next_expectation = static function (string $method) use ($add): Expectation_Interface {
            return $add($method);
        };
        $parent = \get_class($mock);
        while (true) {
            $method = \array_shift($method_names);
            if (empty($method_names)) {
                $expectations = $next_expectation($method);
                break;
            }
            $expectations = $mock->mockery_get_expectations_for($method);
            if ($expectations instanceof Expectation_Director) {
                $demeter_mock_key = $container->get_key_of_demeter_mock_for($method, $parent);
                if (\is_string($demeter_mock_key)) {
                    $mock = self::get_existing_demeter_mock($container, $demeter_mock_key);
                }
            } else {
                $expectations = $next_expectation($method);
                $mock = self::get_new_demeter_mock($container, $parent, $method, $expectations);
            }
            $parent .= '->' . $method;
            $next_expectation = static function (string $method) use ($mock) {
                return $mock->allows($method);
            };
        }
        return $expectations;
    }
    /**
     * Utility method for recursively generating a representation of the given array.
     *
     * @template TArray or array
     *
     * @param TArray $argument
     * @param int    $nesting
     *
     * @return TArray
     */
    private static function cleanup_array(array $argument, $nesting = 3)
    {
        if ($nesting === 0) {
            return '...';
        }
        foreach ($argument as $key => $value) {
            if (\is_array($value)) {
                $argument[$key] = self::cleanup_array($value, $nesting - 1);
                continue;
            }
            if (\is_object($value)) {
                $argument[$key] = self::object_to_array($value, $nesting - 1);
            }
        }
        return $argument;
    }
    /**
     * Utility method used for recursively generating
     * an object or array representation.
     *
     * @template TArgument
     *
     * @param TArgument $argument
     * @param int       $nesting
     *
     * @return mixed
     */
    private static function cleanup_nesting($argument, $nesting)
    {
        if (\is_object($argument)) {
            $object = self::object_to_array($argument, $nesting - 1);
            $object['class'] = \get_class($argument);
            return $object;
        }
        if (\is_array($argument)) {
            return self::cleanup_array($argument, $nesting - 1);
        }
        return $argument;
    }
    /**
     * @param string $fqn
     */
    private static function declare_type($fqn, string $type): void
    {
        $target_code = '<?php ';
        $short_name = $fqn;
        if (\strpos($fqn, '\\')) {
            $parts = \explode('\\', $fqn);
            $short_name = \trim(\array_pop($parts));
            $namespace = \implode('\\', $parts);
            $target_code .= "namespace {$namespace};\n";
        }
        $target_code .= \sprintf('%s %s {} ', $type, $short_name);
        /*
         * We could eval here, but it doesn't play well with the way
         * PHPUnit tries to backup global state and the require definition
         * loader
         */
        $file_name = \tempnam(\sys_get_temp_dir(), 'Mockery');
        \file_put_contents($file_name, $target_code);
        require $file_name;
        self::register_file_for_clean_up($file_name);
    }
    /**
     * Returns all public instance properties.
     *
     * @param object $object
     * @param int    $nesting
     *
     * @return array<string, mixed>
     */
    private static function extract_instance_public_properties($object, $nesting): array
    {
        $reflection = new ReflectionClass($object);
        $properties = $reflection->get_properties(ReflectionProperty::IS_PUBLIC);
        $cleaned_properties = [];
        foreach ($properties as $public_property) {
            if (!$public_property->is_static()) {
                $name = $public_property->get_name();
                try {
                    $cleaned_properties[$name] = self::cleanup_nesting($object->{$name}, $nesting);
                } catch (Throwable $throwable) {
                    $cleaned_properties[$name] = $throwable->get_message();
                }
            }
        }
        return $cleaned_properties;
    }
    /**
     * Gets the string representation
     * of any passed argument.
     *
     * @param mixed $argument
     * @param int   $depth
     *
     * @return mixed
     */
    private static function format_argument($argument, $depth = 0)
    {
        if ($argument instanceof Matcher_Interface) {
            return (string) $argument;
        }
        if (\is_object($argument)) {
            return 'object(' . \get_class($argument) . ')';
        }
        if (\is_int($argument) || \is_float($argument)) {
            return $argument;
        }
        if (\is_array($argument)) {
            if ($depth === 1) {
                $argument = '[...]';
            } else {
                $sample = [];
                foreach ($argument as $key => $value) {
                    $key = \is_int($key) ? $key : \sprintf("'%s'", $key);
                    $value = self::format_argument($value, $depth + 1);
                    $sample[] = \sprintf('%s => %s', $key, $value);
                }
                $argument = '[' . \implode(', ', $sample) . ']';
            }
            return \strlen($argument) > 1000 ? \substr($argument, 0, 1000) . '...]' : $argument;
        }
        if (\is_bool($argument)) {
            return $argument ? 'true' : 'false';
        }
        if (\is_resource($argument)) {
            return 'resource(...)';
        }
        if ($argument === null) {
            return 'NULL';
        }
        return "'" . $argument . "'";
    }
    /**
     * Gets a specific demeter mock from the ones kept by the container.
     *
     * @template TMock of object
     *
     * @param class-string<TMock> $demeterMockKey
     *
     * @return null|((LegacyMockInterface&TMock)|(MockInterface&TMock))
     */
    private static function get_existing_demeter_mock(Container $container, string $demeter_mock_key)
    {
        return $container->get_mocks()[$demeter_mock_key] ?? null;
    }
    /**
     * Gets a new demeter configured
     * mock from the container.
     *
     *
     * @return LegacyMockInterface&MockInterface
     */
    private static function get_new_demeter_mock(Container $container, string $parent, string $method, Expectation_Interface $expectation)
    {
        $new_mock_name = 'demeter_' . \md5($parent) . '_' . $method;
        $parent_mock = $expectation->get_mock();
        if (!$parent_mock instanceof Legacy_Mock_Interface) {
            $mock = $container->mock($new_mock_name);
            $expectation->and_return($mock);
            return $mock;
        }
        $parent_mock_reflection_object = new Reflection_Object($parent_mock);
        if (!$parent_mock_reflection_object->has_method($method)) {
            $mock = $container->mock($new_mock_name);
            $expectation->and_return($mock);
            return $mock;
        }
        $par_ref_method_ret_type = Reflector::get_return_type($parent_mock_reflection_object->get_method($method), true);
        if (!\is_string($par_ref_method_ret_type)) {
            $mock = $container->mock($new_mock_name);
            $expectation->and_return($mock);
            return $mock;
        }
        if ($par_ref_method_ret_type === 'self' || $par_ref_method_ret_type === 'static') {
            $expectation->and_return($parent_mock);
            return $parent_mock;
        }
        $name_builder = new Mock_Name_Builder();
        $name_builder->add_part('\\' . $new_mock_name);
        $mock = self::named_mock($name_builder->build(), ...\array_filter(\explode('|', $par_ref_method_ret_type), static function (string $type): bool {
            return !Reflector::is_reserved_word($type);
        }));
        $expectation->and_return($mock);
        return $mock;
    }
    /**
     * Utility function to turn public properties and public get* and is* method values into an array.
     *
     * @param object $object
     * @param int    $nesting
     */
    private static function object_to_array($object, $nesting = 3): array
    {
        if ($nesting === 0) {
            return ['...'];
        }
        $default_formatter = static function ($object, $nesting): array {
            return ['properties' => self::extract_instance_public_properties($object, $nesting)];
        };
        $class = \get_class($object);
        $formatter = self::get_configuration()->get_object_formatter($class, $default_formatter);
        $array = ['class' => $class, 'identity' => '#' . \md5(\spl_object_hash($object))];
        return \array_merge($array, $formatter($object, $nesting));
    }
}