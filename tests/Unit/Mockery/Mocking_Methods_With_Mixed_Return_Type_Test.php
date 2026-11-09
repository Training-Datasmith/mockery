<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\MyInterface;

/**
 * @coversDefaultClass \Mockery
 */
class Mocking_Methods_With_Mixed_Return_Type_Test extends Mockery_Test_Case
{
    public function testMockingMixedReturnType(): void
    {
        $mock = Mockery::mock(MyInterface::class);

        $mock->should_receive('foo->bar')
            ->and_return('bar');

        self::assertSame('bar', $mock->foo()->bar());
    }
}
