<?php

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery\Generator;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

use function array_map;
use function array_merge;
use function array_unique;

use const PHP_VERSION_ID;

class DefinedTargetClass implements TargetClassInterface
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
        $this->name = $alias ?? $rfc->getName();
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
    public function getAttributes(): array
    {
        if (PHP_VERSION_ID < 80000) {
            return [];
        }

        return array_unique(
            array_merge(
                ['\AllowDynamicProperties'],
                array_map(
                    static function (ReflectionAttribute $attribute): string {
                        return '\\' . $attribute->getName();
                    },
                    $this->rfc->getAttributes()
                )
            )
        );
    }

    /**
     * @return array<class-string,self>
     */
    public function getInterfaces(): array
    {
        return array_map(
            static function (ReflectionClass $interface): self {
                return new self($interface);
            },
            $this->rfc->getInterfaces()
        );
    }

    /**
     * @return list<Method>
     */
    public function getMethods(): array
    {
        return array_map(
            static function (ReflectionMethod $method): Method {
                return new Method($method);
            },
            $this->rfc->getMethods()
        );
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
        return $this->rfc->getNamespaceName();
    }

    public function getShortName(): string
    {
        return $this->rfc->getShortName();
    }

    public function hasInternalAncestor(): bool
    {
        if ($this->rfc->isInternal()) {
            return true;
        }

        $child = $this->rfc;
        while ($parent = $child->getParentClass()) {
            if ($parent->isInternal()) {
                return true;
            }

            $child = $parent;
        }

        return false;
    }

    /**
     * @param  class-string $interface
     */
    public function implementsInterface($interface): bool
    {
        return $this->rfc->implementsInterface($interface);
    }

    public function inNamespace(): bool
    {
        return $this->rfc->inNamespace();
    }

    public function isAbstract(): bool
    {
        return $this->rfc->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->rfc->isFinal();
    }
}
