<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Mock_Interface;
use PHP73\HasUnknownClassAsTypeHintOnMethod;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Class_With_Unknown_Type_Hint_Test extends Mockery_Test_Case
{
    public function testItShouldSuccessfullyBuildTheMock(): void
    {
        $mock = \mock(HasUnknownClassAsTypeHintOnMethod::class);

        self::assertInstanceOf(Mock_Interface::class, $mock);
    }
}
