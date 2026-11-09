<?php

declare(strict_types=1);

namespace Tests\Unit\PHP80;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP80\MethodWithStaticReturnType;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Methods_With_Static_Return_Type_Test extends Mockery_Test_Case
{
    public function testMockingStaticReturnType(): void
    {
        $mock = \mock(MethodWithStaticReturnType::class);

        $mock->should_receive('returnType');

        self::assertSame($mock, $mock->returnType());
    }
}
