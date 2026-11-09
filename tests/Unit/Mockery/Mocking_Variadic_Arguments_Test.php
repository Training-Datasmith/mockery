<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\TestWithVariadicArguments;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Variadic_Arguments_Test extends Mockery_Test_Case
{
    public function testShouldAllowMockingVariadicArguments(): void
    {
        $mock = \mock(TestWithVariadicArguments::class);

        $mock->should_receive('foo')
            ->and_return('notbar');
        self::assertSame('notbar', $mock->foo());
    }
}
