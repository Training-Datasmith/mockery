<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\ClassWithAllLowerCaseMethod;

/**
 * @coversDefaultClass \Mockery
 */
final class MockingAllLowerCasedMethodsTest extends Mockery_Test_Case
{
    public function testItShouldAllowToCallAllLowerCasedMethodAsCamelCased(): void
    {
        Mockery::getConfiguration()->allowMockingNonExistentMethods(true);

        $expected = 'mocked';

        $mock = \mock(ClassWithAllLowerCaseMethod::class);

        $mock->shouldReceive('userExpectsCamelCaseMethod')
            ->andReturn($expected);

        self::assertSame($expected, $mock->userExpectsCamelCaseMethod());
    }
}
