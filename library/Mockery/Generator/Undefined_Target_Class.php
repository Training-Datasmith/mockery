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

use function array_pop;
use function explode;
use function implode;
use function ltrim;
class Undefined_Target_Class implements Target_Class_Interface
{
    /**
     * @var class-string
     */
    private $name;
    /**
     * @param class-string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    /**
     * @return class-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
    /**
     * @param  class-string $name
     */
    public static function factory($name): self
    {
        return new self($name);
    }
    /**
     * @return list<class-string>
     */
    public function get_attributes(): array
    {
        return [];
    }
    /**
     * @return list<self>
     */
    public function get_interfaces(): array
    {
        return [];
    }
    /**
     * @return list<Method>
     */
    public function get_methods(): array
    {
        return [];
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
        $parts = explode('\\', ltrim($this->get_name(), '\\'));
        array_pop($parts);
        return implode('\\', $parts);
    }
    public function get_short_name(): string
    {
        $parts = explode('\\', $this->get_name());
        return array_pop($parts);
    }
    public function has_internal_ancestor(): bool
    {
        return false;
    }
    /**
     * @param  class-string $interface
     */
    public function implements_interface($interface): bool
    {
        return false;
    }
    public function in_namespace(): bool
    {
        return $this->get_namespace_name() !== '';
    }
    public function is_abstract(): bool
    {
        return false;
    }
    public function is_final(): bool
    {
        return false;
    }
}