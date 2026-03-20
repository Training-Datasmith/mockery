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
use function array_merge;
use function class_implements;
use Closure;
use const E_USER_DEPRECATED;
use function get_parent_class;
use Hamcrest\Matcher;
use Hamcrest_Matcher;
use InvalidArgumentException;
use function is_a;
use LogicException;
use Mockery\Matcher\Matcher_Interface;
use const PHP_MAJOR_VERSION;
use function sprintf;
use function strtolower;
use function trigger_error;
class Configuration
{
    /**
     * Boolean assertion of whether we ignore unnecessary mocking of methods,
     * i.e. when method expectations are made, set using a zeroOrMoreTimes()
     * constraint, and then never called. Essentially such expectations are
     * not required and are just taking up test space.
     *
     * @var bool
     */
    protected $_allow_mocking_methods_unnecessarily = true;
    /**
     * Boolean assertion of whether we can mock methods which do not actually
     * exist for the given class or object (ignored for unreal mocks)
     *
     * @var bool
     */
    protected $_allow_mocking_non_existent_method = true;
    /**
     * Constants map
     *
     * e.g. ['class' => ['MY_CONST' => 123, 'OTHER_CONST' => 'foo']]
     *
     * @var array<class-string,array<string,array<scalar>|scalar>>
     */
    protected $_constants_map = [];
    /**
     * Default argument matchers
     *
     * e.g. ['class' => 'matcher']
     *
     * @var array<class-string,class-string>
     */
    protected $_default_matchers = [];
    /**
     * Parameter map for use with PHP internal classes.
     *
     *  e.g. ['class' => ['method' => ['param1', 'param2']]]
     *
     * @var array<class-string,array<string,list<string>>>
     */
    protected $_internal_class_param_map = [];
    /**
     * Custom object formatters
     *
     * e.g. ['class' => static fn($object) => 'formatted']
     *
     * @var array<class-string,Closure>
     */
    protected $_object_formatters = [];
    /**
     * @var QuickDefinitionsConfiguration
     */
    protected $_quick_definitions_configuration;
    /**
     * Boolean assertion is reflection caching enabled or not. It should be
     * always enabled, except when using PHPUnit's --static-backup option.
     *
     * @see https://github.com/mockery/mockery/issues/268
     */
    protected $_reflection_cache_enabled = true;
    public function __construct()
    {
        $this->_quick_definitions_configuration = new Quick_Definitions_Configuration();
    }
    /**
     * Set boolean to allow/prevent unnecessary mocking of methods
     *
     * @param bool $flag
     *
     *
     * @deprecated since 1.4.0
     */
    public function allow_mocking_methods_unnecessarily($flag = true): void
    {
        @trigger_error(sprintf('The %s method is deprecated and will be removed in a future version of Mockery', __METHOD__), E_USER_DEPRECATED);
        $this->_allow_mocking_methods_unnecessarily = (bool) $flag;
    }
    /**
     * Set boolean to allow/prevent mocking of non-existent methods
     *
     * @param bool $flag
     */
    public function allow_mocking_non_existent_methods($flag = true): void
    {
        $this->_allow_mocking_non_existent_method = (bool) $flag;
    }
    /**
     * Disable reflection caching
     *
     * It should be always enabled, except when using
     * PHPUnit's --static-backup option.
     *
     * @see https://github.com/mockery/mockery/issues/268
     */
    public function disable_reflection_cache(): void
    {
        $this->_reflection_cache_enabled = false;
    }
    /**
     * Enable reflection caching
     *
     * It should be always enabled, except when using
     * PHPUnit's --static-backup option.
     *
     * @see https://github.com/mockery/mockery/issues/268
     */
    public function enable_reflection_cache(): void
    {
        $this->_reflection_cache_enabled = true;
    }
    /**
     * Get the map of constants to be used in the mock generator
     *
     * @return array<class-string,array<string,array<scalar>|scalar>>
     */
    public function get_constants_map()
    {
        return $this->_constants_map;
    }
    /**
     * Get the default matcher for a given class
     *
     * @param class-string $class
     *
     * @return null|class-string
     */
    public function get_default_matcher($class): ?string
    {
        $classes = [];
        $parent_class = $class;
        do {
            $classes[] = $parent_class;
            $parent_class = get_parent_class($parent_class);
        } while ($parent_class !== false);
        $classes_and_interfaces = array_merge($classes, class_implements($class));
        foreach ($classes_and_interfaces as $type) {
            if (array_key_exists($type, $this->_default_matchers)) {
                return $this->_default_matchers[$type];
            }
        }
        return null;
    }
    /**
     * Get the parameter map of an internal PHP class method
     *
     * @param class-string $class
     * @param string       $method
     */
    public function get_internal_class_method_param_map($class, $method): ?array
    {
        $class = strtolower($class);
        $method = strtolower($method);
        if (!array_key_exists($class, $this->_internal_class_param_map)) {
            return null;
        }
        if (!array_key_exists($method, $this->_internal_class_param_map[$class])) {
            return null;
        }
        return $this->_internal_class_param_map[$class][$method];
    }
    /**
     * Get the parameter maps of internal PHP classes
     *
     * @return array<class-string,array<string,list<string>>>
     */
    public function get_internal_class_method_param_maps()
    {
        return $this->_internal_class_param_map;
    }
    /**
     * Get the object formatter for a class
     *
     * @param class-string $class
     * @param Closure      $defaultFormatter
     *
     * @return Closure
     */
    public function get_object_formatter($class, $default_formatter)
    {
        $parent_class = $class;
        do {
            $classes[] = $parent_class;
            $parent_class = get_parent_class($parent_class);
        } while ($parent_class !== false);
        $classes_and_interfaces = array_merge($classes, class_implements($class));
        foreach ($classes_and_interfaces as $type) {
            if (array_key_exists($type, $this->_object_formatters)) {
                return $this->_object_formatters[$type];
            }
        }
        return $default_formatter;
    }
    /**
     * Returns the quick definitions configuration
     */
    public function get_quick_definitions(): Quick_Definitions_Configuration
    {
        return $this->_quick_definitions_configuration;
    }
    /**
     * Return flag indicating whether mocking non-existent methods allowed
     *
     * @return bool
     *
     * @deprecated since 1.4.0
     */
    public function mocking_methods_unnecessarily_allowed()
    {
        @trigger_error(sprintf('The %s method is deprecated and will be removed in a future version of Mockery', __METHOD__), E_USER_DEPRECATED);
        return $this->_allow_mocking_methods_unnecessarily;
    }
    /**
     * Return flag indicating whether mocking non-existent methods allowed
     *
     * @return bool
     */
    public function mocking_non_existent_methods_allowed()
    {
        return $this->_allow_mocking_non_existent_method;
    }
    /**
     * Is reflection cache enabled?
     *
     * @return bool
     */
    public function reflection_cache_enabled()
    {
        return $this->_reflection_cache_enabled;
    }
    /**
     * Remove all overridden parameter maps from internal PHP classes.
     */
    public function reset_internal_class_method_param_maps(): void
    {
        $this->_internal_class_param_map = [];
    }
    /**
     * Set a map of constants to be used in the mock generator
     *
     * e.g. ['MyClass' => ['MY_CONST' => 123, 'ARRAY_CONST' => ['foo', 'bar']]]
     *
     * @param array<class-string,array<string,array<scalar>|scalar>> $map
     */
    public function set_constants_map(array $map): void
    {
        $this->_constants_map = $map;
    }
    /**
     * @param class-string $class
     * @param class-string $matcherClass
     *
     * @throws InvalidArgumentException
     */
    public function set_default_matcher(string $class, $matcher_class): void
    {
        $is_hamcrest = is_a($matcher_class, Matcher::class, true) || is_a($matcher_class, Hamcrest_Matcher::class, true);
        if ($is_hamcrest) {
            @trigger_error('Hamcrest package has been deprecated and will be removed in 2.0', E_USER_DEPRECATED);
        }
        if (!$is_hamcrest && !is_a($matcher_class, Matcher_Interface::class, true)) {
            throw new InvalidArgumentException(sprintf("Matcher class must implement %s, '%s' given.", Matcher_Interface::class, $matcher_class));
        }
        $this->_default_matchers[$class] = $matcher_class;
    }
    /**
     * Set a parameter map (array of param signature strings) for the method of an internal PHP class.
     *
     * @param class-string $class
     * @param string       $method
     * @param list<string> $map
     *
     * @throws LogicException
     */
    public function set_internal_class_method_param_map($class, $method, array $map): void
    {
        if (PHP_MAJOR_VERSION > 7) {
            throw new LogicException('Internal class parameter overriding is not available in PHP 8. Incompatible signatures have been reclassified as fatal errors.');
        }
        $class = strtolower($class);
        if (!array_key_exists($class, $this->_internal_class_param_map)) {
            $this->_internal_class_param_map[$class] = [];
        }
        $this->_internal_class_param_map[$class][strtolower($method)] = $map;
    }
    /**
     * Set a custom object formatter for a class
     *
     * @param class-string $class
     * @param Closure      $formatterCallback
     */
    public function set_object_formatter(string $class, $formatter_callback): void
    {
        $this->_object_formatters[$class] = $formatter_callback;
    }
}