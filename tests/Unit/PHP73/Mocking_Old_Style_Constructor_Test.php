<?php

declare(strict_types=1);

namespace Tests\Unit\PHP73;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Mock_Interface;
use PHP73\OldStyleConstructor;

/**
 * @coversDefaultClass \Mockery
 */
class MockingOldStyleConstructorTest extends Mockery_Test_Case
{
    public function testMockClassWithOldStyleConstructorAndArguments(): void
    {
        $double = \mock(OldStyleConstructor::class);

        self::assertInstanceOf(Mock_Interface::class, $double);
        self::assertInstanceOf(OldStyleConstructor::class, $double);
    }
}
