<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\ClassWithAllLowerCaseMethod;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_All_Lower_Cased_Methods_Test extends Mockery_Test_Case
{
    public function testItShouldAllowToCallAllLowerCasedMethodAsCamelCased(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);

        $expected = 'mocked';

        $mock = \mock(ClassWithAllLowerCaseMethod::class);

        $mock->should_receive('userExpectsCamelCaseMethod')
            ->and_return($expected);

        self::assertSame($expected, $mock->userExpectsCamelCaseMethod());
    }
}
