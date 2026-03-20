<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Generator;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function class_alias;
use function class_exists;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function interface_exists;
use function is_object;
use function md5;
use Mockery\Exception;
use function preg_match;
use Serializable;
use function serialize;
use function strpos;
use function strtolower;
use function trait_exists;
/**
 * This class describes the configuration of mocks and hides away some of the
 * reflection implementation
 */
class Mock_Configuration
{
    /**
     * Instance cache of all methods
     *
     * @var list<Method>
     */
    protected $all_methods = [];
    /**
     * Methods that should specifically not be mocked
     *
     * This is currently populated with stuff we don't know how to deal with, should really be somewhere else
     */
    protected $black_listed_methods = [];
    protected $constants_map = [];
    /**
     * An instance mock is where we override the original class before it's autoloaded
     *
     * @var bool
     */
    protected $instance_mock = false;
    /**
     * If true, overrides original class destructor
     *
     * @var bool
     */
    protected $mock_original_destructor = false;
    /**
     * The class name we'd like to use for a generated mock
     *
     * @var string|null
     */
    protected $name;
    /**
     * Param overrides
     *
     * @var array<string,mixed>
     */
    protected $parameter_overrides = [];
    /**
     * A class that we'd like to mock
     * @var TargetClassInterface|null
     */
    protected $target_class;
    /**
     * @var class-string|null
     */
    protected $target_class_name;
    /**
     * @var array<class-string>
     */
    protected $target_interface_names = [];
    /**
     * A number of interfaces we'd like to mock, keyed by name to attempt to keep unique
     *
     * @var array<TargetClassInterface>
     */
    protected $target_interfaces = [];
    /**
     * An object we'd like our mock to proxy to
     *
     * @var object|null
     */
    protected $target_object;
    /**
     * @var array<string>
     */
    protected $target_trait_names = [];
    /**
     * A number of traits we'd like to mock, keyed by name to attempt to keep unique
     *
     * @var array<string,DefinedTargetClass>
     */
    protected $target_traits = [];
    /**
     * If not empty, only these methods will be mocked
     *
     * @var array<string>
     */
    protected $white_listed_methods = [];
    /**
     * @param array<class-string|object>         $targets
     * @param array<string>                      $blackListedMethods
     * @param array<string>                      $whiteListedMethods
     * @param string|null                        $name
     * @param bool                               $instanceMock
     * @param array<string,mixed>                $parameterOverrides
     * @param bool                               $mockOriginalDestructor
     * @param array<string,array<scalar>|scalar> $constantsMap
     */
    public function __construct(array $targets = [], array $black_listed_methods = [], array $white_listed_methods = [], $name = null, $instance_mock = false, array $parameter_overrides = [], $mock_original_destructor = false, array $constants_map = [])
    {
        $this->add_targets($targets);
        $this->black_listed_methods = $black_listed_methods;
        $this->white_listed_methods = $white_listed_methods;
        $this->name = $name;
        $this->instance_mock = $instance_mock;
        $this->parameter_overrides = $parameter_overrides;
        $this->mock_original_destructor = $mock_original_destructor;
        $this->constants_map = $constants_map;
    }
    /**
     * Generate a suitable name based on the config
     *
     * @return string
     */
    public function generate_name()
    {
        $name_builder = new Mock_Name_Builder();
        $target_object = $this->get_target_object();
        if ($target_object !== null) {
            $class_name = get_class($target_object);
            $name_builder->add_part(strpos($class_name, '@') !== false ? md5($class_name) : $class_name);
        }
        $target_class = $this->get_target_class();
        if ($target_class instanceof Target_Class_Interface) {
            $class_name = $target_class->get_name();
            $name_builder->add_part(strpos($class_name, '@') !== false ? md5($class_name) : $class_name);
        }
        foreach ($this->get_target_interfaces() as $target_interface) {
            $name_builder->add_part($target_interface->get_name());
        }
        return $name_builder->build();
    }
    /**
     * @return array<string>
     */
    public function get_black_listed_methods()
    {
        return $this->black_listed_methods;
    }
    /**
     * @return array<string,scalar|array<scalar>>
     */
    public function get_constants_map()
    {
        return $this->constants_map;
    }
    /**
     * Attempt to create a hash of the configuration, in order to allow caching
     *
     * @TODO workout if this will work
     */
    public function get_hash(): string
    {
        $vars = ['targetClassName' => $this->target_class_name, 'targetInterfaceNames' => $this->target_interface_names, 'targetTraitNames' => $this->target_trait_names, 'name' => $this->name, 'blackListedMethods' => $this->black_listed_methods, 'whiteListedMethod' => $this->white_listed_methods, 'instanceMock' => $this->instance_mock, 'parameterOverrides' => $this->parameter_overrides, 'mockOriginalDestructor' => $this->mock_original_destructor];
        return md5(serialize($vars));
    }
    /**
     * Gets a list of methods from the classes, interfaces and objects and filters them appropriately.
     * Lot's of filtering going on, perhaps we could have filter classes to iterate through
     *
     * @return list<Method>
     */
    public function get_methods_to_mock(): array
    {
        $methods = $this->get_all_methods();
        foreach ($methods as $key => $method) {
            if ($method->is_final()) {
                unset($methods[$key]);
            }
        }
        /**
         * Whitelist trumps everything else
         */
        $white_listed_methods = $this->get_white_listed_methods();
        if ($white_listed_methods !== []) {
            $whitelist = array_map('strtolower', $white_listed_methods);
            return array_filter($methods, static function (\Mockery\Generator\Method $method) use ($whitelist): bool {
                if ($method->is_abstract()) {
                    return true;
                }
                return in_array(strtolower($method->get_name()), $whitelist, true);
            });
        }
        /**
         * Remove blacklisted methods
         */
        $black_listed_methods = $this->get_black_listed_methods();
        if ($black_listed_methods !== []) {
            $blacklist = array_map('strtolower', $black_listed_methods);
            $methods = array_filter($methods, static function (\Mockery\Generator\Method $method) use ($blacklist): bool {
                return !in_array(strtolower($method->get_name()), $blacklist, true);
            });
        }
        /**
         * Internal objects can not be instantiated with newInstanceArgs and if
         * they implement Serializable, unserialize will have to be called. As
         * such, we can't mock it and will need a pass to add a dummy
         * implementation
         */
        $target_class = $this->get_target_class();
        if ($target_class !== null && $target_class->implements_interface(Serializable::class) && $target_class->has_internal_ancestor()) {
            $methods = array_filter($methods, static function (\Mockery\Generator\Method $method): bool {
                return $method->get_name() !== 'unserialize';
            });
        }
        return array_values($methods);
    }
    /**
     * @return string|null
     */
    public function get_name()
    {
        return $this->name;
    }
    public function get_namespace_name(): string
    {
        $parts = explode('\\', $this->get_name());
        array_pop($parts);
        if ($parts !== []) {
            return implode('\\', $parts);
        }
        return '';
    }
    /**
     * @return array<string,mixed>
     */
    public function get_parameter_overrides()
    {
        return $this->parameter_overrides;
    }
    public function get_short_name(): string
    {
        $parts = explode('\\', $this->get_name());
        return array_pop($parts);
    }
    /**
     * @return null|TargetClassInterface
     */
    public function get_target_class()
    {
        if ($this->target_class) {
            return $this->target_class;
        }
        if (!$this->target_class_name) {
            return null;
        }
        if (class_exists($this->target_class_name)) {
            $alias = null;
            if (strpos($this->target_class_name, '@') !== false) {
                $alias = (new Mock_Name_Builder())->add_part('anonymous_class')->add_part(md5($this->target_class_name))->build();
                class_alias($this->target_class_name, $alias);
            }
            $dtc = Defined_Target_Class::factory($this->target_class_name, $alias);
            if ($this->get_target_object() === null && $dtc->is_final()) {
                throw new Exception('The class ' . $this->target_class_name . ' is marked final and its methods' . ' cannot be replaced. Classes marked final can be passed in' . ' to \Mockery::mock() as instantiated objects to create a' . ' partial mock, but only if the mock is not subject to type' . ' hinting checks.');
            }
            $this->target_class = $dtc;
        } else {
            $this->target_class = Undefined_Target_Class::factory($this->target_class_name);
        }
        return $this->target_class;
    }
    /**
     * @return class-string|null
     */
    public function get_target_class_name()
    {
        return $this->target_class_name;
    }
    /**
     * @return list<TargetClassInterface>
     */
    public function get_target_interfaces()
    {
        if ($this->target_interfaces !== []) {
            return $this->target_interfaces;
        }
        foreach ($this->target_interface_names as $target_interface) {
            if (!interface_exists($target_interface)) {
                $this->target_interfaces[] = Undefined_Target_Class::factory($target_interface);
                continue;
            }
            $dtc = Defined_Target_Class::factory($target_interface);
            $extended_interfaces = array_keys($dtc->get_interfaces());
            $extended_interfaces[] = $target_interface;
            $traversable_found = false;
            $iterator_shifted_to_front = false;
            foreach ($extended_interfaces as $interface) {
                if (!$traversable_found && preg_match('/^\?Iterator(|Aggregate)$/i', $interface)) {
                    break;
                }
                if (preg_match('/^\\\\?IteratorAggregate$/i', $interface)) {
                    $this->target_interfaces[] = Defined_Target_Class::factory('\IteratorAggregate');
                    $iterator_shifted_to_front = true;
                    continue;
                }
                if (preg_match('/^\\\\?Iterator$/i', $interface)) {
                    $this->target_interfaces[] = Defined_Target_Class::factory('\Iterator');
                    $iterator_shifted_to_front = true;
                    continue;
                }
                if (preg_match('/^\\\\?Traversable$/i', $interface)) {
                    $traversable_found = true;
                }
            }
            if ($traversable_found && !$iterator_shifted_to_front) {
                $this->target_interfaces[] = Defined_Target_Class::factory('\IteratorAggregate');
            }
            /**
             * We never straight up implement Traversable
             */
            $is_traversable = preg_match('/^\\\\?Traversable$/i', $target_interface);
            if ($is_traversable === 0 || $is_traversable === false) {
                $this->target_interfaces[] = $dtc;
            }
        }
        return $this->target_interfaces = array_unique($this->target_interfaces);
    }
    /**
     * @return object|null
     */
    public function get_target_object()
    {
        return $this->target_object;
    }
    /**
     * @return list<TargetClassInterface>
     */
    public function get_target_traits()
    {
        if ($this->target_traits !== []) {
            return $this->target_traits;
        }
        foreach ($this->target_trait_names as $target_trait) {
            $this->target_traits[] = Defined_Target_Class::factory($target_trait);
        }
        $this->target_traits = array_unique($this->target_traits);
        // just in case
        return $this->target_traits;
    }
    /**
     * @return array<string>
     */
    public function get_white_listed_methods()
    {
        return $this->white_listed_methods;
    }
    /**
     * @return bool
     */
    public function is_instance_mock()
    {
        return $this->instance_mock;
    }
    /**
     * @return bool
     */
    public function is_mock_original_destructor()
    {
        return $this->mock_original_destructor;
    }
    /**
     * @param  class-string $className
     */
    public function rename($class_name): self
    {
        $targets = [];
        if ($this->target_class_name) {
            $targets[] = $this->target_class_name;
        }
        if ($this->target_interface_names) {
            $targets = array_merge($targets, $this->target_interface_names);
        }
        if ($this->target_trait_names) {
            $targets = array_merge($targets, $this->target_trait_names);
        }
        if ($this->target_object) {
            $targets[] = $this->target_object;
        }
        return new self($targets, $this->black_listed_methods, $this->white_listed_methods, $class_name, $this->instance_mock, $this->parameter_overrides, $this->mock_original_destructor, $this->constants_map);
    }
    /**
     * We declare the __callStatic method to handle undefined stuff, if the class
     * we're mocking has also defined it, we need to comply with their interface
     *
     * @return bool
     */
    public function requires_call_static_type_hint_removal()
    {
        foreach ($this->get_all_methods() as $method) {
            if ($method->get_name() === '__callStatic') {
                $params = $method->get_parameters();
                if (!array_key_exists(1, $params)) {
                    return false;
                }
                return !$params[1]->is_array();
            }
        }
        return false;
    }
    /**
     * We declare the __call method to handle undefined stuff, if the class
     * we're mocking has also defined it, we need to comply with their interface
     *
     * @return bool
     */
    public function requires_call_type_hint_removal()
    {
        foreach ($this->get_all_methods() as $method) {
            if ($method->get_name() === '__call') {
                $params = $method->get_parameters();
                return !$params[1]->is_array();
            }
        }
        return false;
    }
    /**
     * @param class-string|object $target
     */
    protected function add_target($target)
    {
        if (is_object($target)) {
            $this->set_target_object($target);
            $this->set_target_class_name(get_class($target));
            return;
        }
        if ($target[0] !== '\\') {
            $target = '\\' . $target;
        }
        if (class_exists($target)) {
            $this->set_target_class_name($target);
            return;
        }
        if (interface_exists($target)) {
            $this->add_target_interface_name($target);
            return;
        }
        if (trait_exists($target)) {
            $this->add_target_trait_name($target);
            return;
        }
        /**
         * Default is to set as class, or interface if class already set
         *
         * Don't like this condition, can't remember what the default
         * targetClass is for
         */
        if ($this->get_target_class_name()) {
            $this->add_target_interface_name($target);
            return;
        }
        $this->set_target_class_name($target);
    }
    /**
     * If we attempt to implement Traversable,
     * we must ensure we are also implementing either Iterator or IteratorAggregate,
     * and that whichever one it is comes before Traversable in the list of implements.
     *
     * @param class-string $targetInterface
     */
    protected function add_target_interface_name($target_interface)
    {
        $this->target_interface_names[] = $target_interface;
    }
    /**
     * @param array<class-string> $interfaces
     */
    protected function add_targets($interfaces)
    {
        foreach ($interfaces as $interface) {
            $this->add_target($interface);
        }
    }
    /**
     * @param class-string $targetTraitName
     */
    protected function add_target_trait_name($target_trait_name)
    {
        $this->target_trait_names[] = $target_trait_name;
    }
    /**
     * @return list<Method>
     */
    protected function get_all_methods()
    {
        if ($this->all_methods) {
            return $this->all_methods;
        }
        $classes = $this->get_target_interfaces();
        if ($this->get_target_class()) {
            $classes[] = $this->get_target_class();
        }
        $methods = [];
        foreach ($classes as $class) {
            $methods = array_merge($methods, $class->get_methods());
        }
        foreach ($this->get_target_traits() as $trait) {
            foreach ($trait->get_methods() as $method) {
                if ($method->is_abstract()) {
                    $methods[] = $method;
                }
            }
        }
        $names = [];
        $methods = array_filter($methods, static function (\Mockery\Generator\Method $method) use (&$names): bool {
            if (in_array($method->get_name(), $names, true)) {
                return false;
            }
            $names[] = $method->get_name();
            return true;
        });
        return $this->all_methods = $methods;
    }
    /**
     * @param class-string $targetClassName
     */
    protected function set_target_class_name($target_class_name)
    {
        $this->target_class_name = $target_class_name;
    }
    /**
     * @param object $object
     */
    protected function set_target_object($object)
    {
        $this->target_object = $object;
    }
}