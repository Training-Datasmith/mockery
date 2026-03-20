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
use ReflectionMethod;
use ReflectionParameter;
/**
 * @mixin ReflectionMethod
 */
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
    /**
     * @template TArgs
     * @template TMixed
     *
     * @param array<TArgs> $args
     * @return TMixed
     */
    public function __call(string $method, array $args)
    {
        /** @var TMixed */
        return $this->method->{$method}(...$args);
    }
    /**
     * @return list<Parameter>
     */
    public function get_parameters(): array
    {
        return array_map(static function (ReflectionParameter $parameter): \Mockery\Generator\Parameter {
            return new Parameter($parameter);
        }, $this->method->get_parameters());
    }
    /**
     * @return null|string
     */
    public function get_return_type()
    {
        return Reflector::get_return_type($this->method);
    }
}