<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\ReturnTypeIterableTypeHint;

/**
 * @coversDefaultClass \Mockery
 */
final class MockClassWithIterableReturnTypeTest extends Mockery_Test_Case
{
    public function testMockingIterableReturnType(): void
    {
        $mock = \mock(ReturnTypeIterableTypeHint::class);

        $mock->expects('returnIterable');

        self::assertSame([], $mock->returnIterable());
    }
}
