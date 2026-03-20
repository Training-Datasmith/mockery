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

use function class_exists;
use Mockery\Reflector;
use ReflectionClass;
use ReflectionParameter;
/**
 * @mixin ReflectionParameter
 */
class Parameter
{
    /**
     * @var int
     */
    private static $parameter_counter = 0;
    /**
     * @var ReflectionParameter
     */
    private $rfp;
    public function __construct(ReflectionParameter $rfp)
    {
        $this->rfp = $rfp;
    }
    /**
     * Proxy all method calls to the reflection parameter.
     *
     * @template TMixed
     * @template TResult
     *
     * @param array<TMixed> $args
     * @return TResult
     */
    public function __call(string $method, array $args)
    {
        /** @var TResult */
        return $this->rfp->{$method}(...$args);
    }
    /**
     * Get the reflection class for the parameter type, if it exists.
     *
     * This will be null if there was no type, or it was a scalar or a union.
     *
     * @return null|ReflectionClass
     *
     * @deprecated since 1.3.3 and will be removed in 2.0.
     */
    public function get_class()
    {
        $type_hint = Reflector::get_type_hint($this->rfp, true);
        return class_exists($type_hint) ? Defined_Target_Class::factory($type_hint, false) : null;
    }
    /**
     * Get the name of the parameter.
     *
     * Some internal classes have funny looking definitions!
     *
     * @return string
     */
    public function get_name()
    {
        $name = $this->rfp->get_name();
        if (!$name || $name === '...') {
            return 'arg' . self::$parameter_counter++;
        }
        return $name;
    }
    /**
     * Get the string representation for the paramater type.
     *
     * @return null|string
     */
    public function get_type_hint()
    {
        return Reflector::get_type_hint($this->rfp);
    }
    /**
     * Get the string representation for the paramater type.
     *
     *
     * @deprecated since 1.3.2 and will be removed in 2.0. Use getTypeHint() instead.
     */
    public function get_type_hint_as_string(): string
    {
        return (string) Reflector::get_type_hint($this->rfp, true);
    }
    /**
     * Determine if the parameter is an array.
     *
     * @return bool
     */
    public function is_array()
    {
        return Reflector::is_array($this->rfp);
    }
    /**
     * Determine if the parameter is variadic.
     */
    public function is_variadic(): bool
    {
        return $this->rfp->is_variadic();
    }
}