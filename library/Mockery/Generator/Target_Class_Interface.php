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

interface Target_Class_Interface
{
    /**
     * Returns a new instance of the current TargetClassInterface's implementation.
     *
     * @param class-string $name
     *
     * @return TargetClassInterface
     */
    public static function factory($name);
    /**
     * Returns the targetClass's attributes.
     *
     * @return array<class-string>
     */
    public function get_attributes();
    /**
     * Returns the targetClass's interfaces.
     *
     * @return array<TargetClassInterface>
     */
    public function get_interfaces();
    /**
     * Returns the targetClass's methods.
     *
     * @return array<Method>
     */
    public function get_methods();
    /**
     * Returns the targetClass's name.
     *
     * @return class-string
     */
    public function get_name();
    /**
     * Returns the targetClass's namespace name.
     *
     * @return string
     */
    public function get_namespace_name();
    /**
     * Returns the targetClass's short name.
     *
     * @return string
     */
    public function get_short_name();
    /**
     * Returns whether the targetClass has
     * an internal ancestor.
     *
     * @return bool
     */
    public function has_internal_ancestor();
    /**
     * Returns whether the targetClass is in
     * the passed interface.
     *
     * @param class-string|string $interface
     *
     * @return bool
     */
    public function implements_interface($interface);
    /**
     * Returns whether the targetClass is in namespace.
     *
     * @return bool
     */
    public function in_namespace();
    /**
     * Returns whether the targetClass is abstract.
     *
     * @return bool
     */
    public function is_abstract();
    /**
     * Returns whether the targetClass is final.
     *
     * @return bool
     */
    public function is_final();
}