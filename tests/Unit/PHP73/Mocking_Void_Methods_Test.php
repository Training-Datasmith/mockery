<?php

declare(strict_types=1);

namespace Tests\Unit\PHP73;

use PHP73\MethodWithVoidReturnType;
use Tests\Unit\Abstract_Test_Case;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Void_Methods_Test extends Abstract_Test_Case
{
    public function testItCanStubAndMockVoidMethods(): void
    {
        $mock = \mock(MethodWithVoidReturnType::class);

        $mock->expects('foo');

        $mock->foo();
    }

    public function testItShouldSuccessfullyBuildTheMock(): void
    {
        self::assertInstanceOf(MethodWithVoidReturnType::class, \mock(MethodWithVoidReturnType::class));
    }
}
