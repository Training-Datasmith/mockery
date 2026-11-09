<?php

declare(strict_types=1);

namespace Tests\Unit\PHP80;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Legacy_Mock_Interface;
use Mockery\Mock_Interface;
use PHP80\PHP80TestInterface;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_With_Closure_As_Last_Argument_Test extends Mockery_Test_Case
{
    public function testIfClosureIsPassedAsLastArgumentToMockItIsCalledWithMockObject(): void
    {
        $mock = Mockery::mock(
            PHP80TestInterface::class,
            static function (Legacy_Mock_Interface|Mock_Interface $mock): void {
                $mock->expects('blm')
                    ->and_return('#BlackLivesMatter');
            }
        );

        self::assertInstanceOf(PHP80TestInterface::class, $mock);

        self::assertSame('#BlackLivesMatter', $mock->blm());
    }
}
