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
use Mockery\Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
class Method
{
    /**
     * @var ReflectionMethod
     */
    private $method;
    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
    }
    public function get_name(): string
    {
        return $this->method->getName();
    }
    public function get_declaring_class(): ReflectionClass
    {
        return $this->method->getDeclaringClass();
    }
    public function is_abstract(): bool
    {
        return $this->method->isAbstract();
    }
    public function is_final(): bool
    {
        return $this->method->isFinal();
    }
    public function is_public(): bool
    {
        return $this->method->isPublic();
    }
    public function is_protected(): bool
    {
        return $this->method->isProtected();
    }
    public function is_static(): bool
    {
        return $this->method->isStatic();
    }
    public function returns_reference(): bool
    {
        return $this->method->returnsReference();
    }
    /**
     * @return list<Parameter>
     */
    public function get_parameters(): array
    {
        return array_map(static function (ReflectionParameter $parameter): \Mockery\Generator\Parameter {
            return new Parameter($parameter);
        }, $this->method->getParameters());
    }
    /**
     * @return null|string
     */
    public function get_return_type()
    {
        return Reflector::get_return_type($this->method);
    }
}
