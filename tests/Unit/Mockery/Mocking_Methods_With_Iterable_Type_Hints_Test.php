<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\MethodWithIterableTypeHints;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Methods_With_Iterable_Type_Hints_Test extends Mockery_Test_Case
{
    public function testItShouldSuccessfullyBuildTheMock(): void
    {
        $mock = \mock(MethodWithIterableTypeHints::class);

        self::assertInstanceOf(MethodWithIterableTypeHints::class, $mock);
    }
}
