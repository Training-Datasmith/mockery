<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\ClassWithStaticMethods;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Static_Methods_Called_Object_Style_Test extends Mockery_Test_Case
{
    public function testProtectedStaticMethodCalledObjectStyleMockWithNotAllowingMockingOfNonExistentMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(ClassWithStaticMethods::class);
        $mock->should_allow_mocking_protected_methods();
        $mock->should_receive('protectedBar')
            ->and_return(true);
        self::assertTrue($mock->protectedBar());
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testStaticMethodCalledObjectStyleMock(): void
    {
        $mock = \mock(ClassWithStaticMethods::class);
        $mock->should_receive('foo')
            ->and_return(true);
        self::assertTrue($mock->foo());
    }

    public function testStaticMethodCalledObjectStyleMockWithNotAllowingMockingOfNonExistentMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(ClassWithStaticMethods::class);
        $mock->should_receive('foo')
            ->and_return(true);
        self::assertTrue($mock->foo());
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }
}
