<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\MethodWithNullableTypedParameter;
use PHP73\MethodWithParametersWithDefaultValues;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Methods_With_Nullable_Parameters_Test extends Mockery_Test_Case
{
    public function testItCanHandleDefaultParameters(): void
    {
        $mock = \mock(MethodWithParametersWithDefaultValues::class);

        self::assertInstanceOf(MethodWithParametersWithDefaultValues::class, $mock);
    }

    public function testItCanHandleNullableTypedParameters(): void
    {
        $mock = \mock(MethodWithNullableTypedParameter::class);

        self::assertInstanceOf(MethodWithNullableTypedParameter::class, $mock);
    }
}
