<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\Bad_Method_Call_Exception;
use PHP73\TestWithMethodOverloading;
use PHP73\TestWithMethodOverloadingWithoutCall;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Class_With_Method_Overloading_Test extends Mockery_Test_Case
{
    public function testCreateMockForClassWithMethodOverloading(): void
    {
        $mock = \mock(TestWithMethodOverloading::class)->make_partial();

        self::assertInstanceOf(TestWithMethodOverloading::class, $mock);

        self::assertSame(42, $mock->theAnswer());
    }

    public function testCreateMockForClassWithMethodOverloadingWithExistingMethod(): void
    {
        $mock = \mock(TestWithMethodOverloading::class)->make_partial();

        self::assertInstanceOf(TestWithMethodOverloading::class, $mock);

        self::assertSame(1, $mock->thisIsRealMethod());
    }

    public function testThrowsWhenMethodDoesNotExist(): void
    {
        $mock = \mock(TestWithMethodOverloadingWithoutCall::class)->make_partial();

        self::assertInstanceOf(TestWithMethodOverloadingWithoutCall::class, $mock);

        $this->expectException(Bad_Method_Call_Exception::class);

        $mock->randomMethod();
    }
}
