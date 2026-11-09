<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Container;
use PHP73\MethodWithNullableReturnType;
use TypeError;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Nullable_Methods_Test extends Mockery_Test_Case
{
    /**
     * @var Container
     */
    private $container;

    public function testItAllowsReturningNullForNullableIntReturnTypes(): void
    {
        $double = Mockery::mock(MethodWithNullableReturnType::class);

        $double->should_receive('nullableInt')
            ->and_return_null();

        self::assertNull($double->nullableInt());
    }

    public function testItAllowsReturningNullForNullableObjectReturnTypes(): void
    {
        $double = Mockery::mock(MethodWithNullableReturnType::class);

        $double->should_receive('nullableClass')
            ->and_return_null();

        self::assertNull($double->nullableClass());
    }

    public function testItAllowsReturningNullForNullableStringReturnTypes(): void
    {
        $double = Mockery::mock(MethodWithNullableReturnType::class);

        $double->should_receive('nullableString')
            ->and_return_null();

        self::assertNull($double->nullableString());
    }

    public function testItReturnsNullOnCallsToIgnoredMethodsOfSpiesIfReturnTypeIsNullable(): void
    {
        $double = Mockery::spy(MethodWithNullableReturnType::class);

        self::assertNull($double->nullableClass());
    }

    public function testItShouldAllowClassToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullableClass')
            ->and_return(new MethodWithNullableReturnType())
            ->once();

        $mock->nonNullableClass();
    }

    public function testItShouldAllowNonNullableTypeToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullablePrimitive')
            ->and_return('a string')
            ->once();
        $mock->nonNullablePrimitive();
    }

    public function testItShouldAllowNullableClassToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullableClass')
            ->and_return(null)
            ->once();
        $mock->nullableClass();
    }

    public function testItShouldAllowNullableSelfToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullableSelf')
            ->and_return(null)
            ->once();
        $mock->nullableSelf();
    }

    public function testItShouldAllowNullableSelfToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullableSelf')
            ->and_return(new MethodWithNullableReturnType())
            ->once();
        $mock->nullableSelf();
    }

    public function testItShouldAllowNullalbeClassToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullableClass')
            ->and_return(new MethodWithNullableReturnType())
            ->once();
        $mock->nullableClass();
    }

    public function testItShouldAllowPrimitiveNullableToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullablePrimitive')
            ->and_return(null)
            ->once();
        $mock->nullablePrimitive();
    }

    public function testItShouldAllowPrimitiveNullableToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nullablePrimitive')
            ->and_return('a string')
            ->once();
        $mock->nullablePrimitive();
    }

    public function testItShouldAllowSelfToBeSet(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullableSelf')
            ->and_return(new MethodWithNullableReturnType())
            ->once();
        $mock->nonNullableSelf();
    }

    public function testItShouldNotAllowClassToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullableClass')
            ->and_return(null);
        $this->expectException(TypeError::class);
        $mock->nonNullableClass();
    }

    public function testItShouldNotAllowNonNullToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullablePrimitive')
            ->and_return(null);
        $this->expectException(TypeError::class);
        $mock->nonNullablePrimitive();
    }

    public function testItShouldNotAllowSelfToBeNull(): void
    {
        $mock = \mock(MethodWithNullableReturnType::class);

        $mock->should_receive('nonNullableSelf')
            ->and_return(null);
        $this->expectException(TypeError::class);
        $mock->nonNullableSelf();
    }
}
