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

use function array_map;
use function array_merge;
use function array_unique;
use const PHP_VERSION_ID;
use Reflection_Attribute;
use ReflectionClass;
use ReflectionMethod;
class Defined_Target_Class implements Target_Class_Interface
{
    /**
     * @var class-string
     */
    private $name;
    /**
     * @var ReflectionClass
     */
    private $rfc;
    /**
     * @param class-string|null $alias
     */
    public function __construct(ReflectionClass $rfc, $alias = null)
    {
        $this->rfc = $rfc;
        $this->name = $alias ?? $rfc->get_name();
    }
    /**
     * @return class-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
    /**
     * @param  class-string      $name
     * @param  class-string|null $alias
     */
    public static function factory($name, $alias = null): self
    {
        return new self(new ReflectionClass($name), $alias);
    }
    /**
     * @return list<class-string>
     */
    public function get_attributes(): array
    {
        if (PHP_VERSION_ID < 80000) {
            return [];
        }
        return array_unique(array_merge(['\AllowDynamicProperties'], array_map(static function (Reflection_Attribute $attribute): string {
            return '\\' . $attribute->get_name();
        }, $this->rfc->get_attributes())));
    }
    /**
     * @return array<class-string,self>
     */
    public function get_interfaces(): array
    {
        return array_map(static function (ReflectionClass $interface): self {
            return new self($interface);
        }, $this->rfc->get_interfaces());
    }
    /**
     * @return list<Method>
     */
    public function get_methods(): array
    {
        return array_map(static function (ReflectionMethod $method): Method {
            return new Method($method);
        }, $this->rfc->get_methods());
    }
    /**
     * @return class-string
     */
    public function get_name()
    {
        return $this->name;
    }
    public function get_namespace_name(): string
    {
        return $this->rfc->get_namespace_name();
    }
    public function get_short_name(): string
    {
        return $this->rfc->get_short_name();
    }
    public function has_internal_ancestor(): bool
    {
        if ($this->rfc->is_internal()) {
            return true;
        }
        $child = $this->rfc;
        while ($parent = $child->get_parent_class()) {
            if ($parent->is_internal()) {
                return true;
            }
            $child = $parent;
        }
        return false;
    }
    /**
     * @param  class-string $interface
     */
    public function implements_interface($interface): bool
    {
        return $this->rfc->implements_interface($interface);
    }
    public function in_namespace(): bool
    {
        return $this->rfc->in_namespace();
    }
    public function is_abstract(): bool
    {
        return $this->rfc->is_abstract();
    }
    public function is_final(): bool
    {
        return $this->rfc->is_final();
    }
}