<?php

declare(strict_types=1);

namespace Tests\Unit\PHP80;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP80\MethodWithStaticReturnType;

/**
 * @coversDefaultClass \Mockery
 */
final class MockingMethodsWithStaticReturnTypeTest extends Mockery_Test_Case
{
    public function testMockingStaticReturnType(): void
    {
        $mock = \mock(MethodWithStaticReturnType::class);

        $mock->shouldReceive('returnType');

        self::assertSame($mock, $mock->returnType());
    }
}
