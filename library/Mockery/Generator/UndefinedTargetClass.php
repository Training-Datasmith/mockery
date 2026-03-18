<?php

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

class UndefinedTargetClass implements TargetClassInterface
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
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * @return list<self>
     */
    public function getInterfaces(): array
    {
        return [];
    }

    /**
     * @return list<Method>
     */
    public function getMethods(): array
    {
        return [];
    }

    /**
     * @return class-string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getNamespaceName(): string
    {
        $parts = explode('\\', ltrim($this->getName(), '\\'));
        array_pop($parts);
        return implode('\\', $parts);
    }

    public function getShortName(): string
    {
        $parts = explode('\\', $this->getName());
        return array_pop($parts);
    }

    public function hasInternalAncestor(): bool
    {
        return false;
    }

    /**
     * @param  class-string $interface
     */
    public function implementsInterface($interface): bool
    {
        return false;
    }

    public function inNamespace(): bool
    {
        return $this->getNamespaceName() !== '';
    }

    public function isAbstract(): bool
    {
        return false;
    }

    public function isFinal(): bool
    {
        return false;
    }
}
