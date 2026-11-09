<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Container;
use Mockery\Exception\Runtime_Exception;
use Mockery\Mock_Interface;
use PHP73\NameOfAbstract;
use PHP73\NameOfExistingClass;
use PHP73\NameOfExistingClassWithDestructor;
use PHP73\NameOfInterface;
use SplFileInfo;

/**
 * Ad-hoc unit tests for various scenarios reported by users
 * @coversDefaultClass \Mockery
 */
final class Adhoc_Test extends Mockery_Test_Case
{
    protected $container;

    protected function mockery_test_set_up(): void
    {
        $this->container = new Container(Mockery::get_default_generator(), Mockery::get_default_loader());
    }

    public function mockery_test_tear_down(): void
    {
        $this->container->mockery_close();
    }

    public function testInvalidCountExceptionThrowsRuntimeExceptionOnIllegalComparativeSymbol(): void
    {
        $this->expectException(Runtime_Exception::class);
        $e = new \Mockery\Exception\Invalid_Count_Exception();
        $e->set_expected_count_comparative('X');
    }

    public function testMockeryConstructAndDestructIsCalled(): void
    {
        NameOfExistingClassWithDestructor::$isDestructorWasCalled = false;

        $this->container->mock(NameOfExistingClassWithDestructor::class, []);
        // Clear references to trigger destructor
        $this->container->mockery_close();
        self::assertTrue(NameOfExistingClassWithDestructor::$isDestructorWasCalled);
    }

    public function testMockeryConstructAndDestructIsNotCalled(): void
    {
        NameOfExistingClassWithDestructor::$isDestructorWasCalled = false;
        // We pass no arguments in constructor, so it's not being called. Then destructor shouldn't be called too.
        $this->container->mock(NameOfExistingClassWithDestructor::class);
        // Clear references to trigger destructor
        $this->container->mockery_close();
        self::assertFalse(NameOfExistingClassWithDestructor::$isDestructorWasCalled);
    }

    public function testMockeryInterfaceForAbstract(): void
    {
        $m = $this->container->mock(NameOfAbstract::class);
        self::assertInstanceOf(Mock_Interface::class, $m);
    }

    public function testMockeryInterfaceForClass(): void
    {
        $m = $this->container->mock(SplFileInfo::class);
        self::assertInstanceOf(Mock_Interface::class, $m);
    }

    public function testMockeryInterfaceForInterface(): void
    {
        $m = $this->container->mock(NameOfInterface::class);
        self::assertInstanceOf(Mock_Interface::class, $m);
    }

    public function testMockeryInterfaceForNonExistingClass(): void
    {
        $m = $this->container->mock('ABC_IDontExist');
        self::assertInstanceOf(Mock_Interface::class, $m);
    }

    public function testSimplestMockCreation(): void
    {
        $m = $this->container->mock(NameOfExistingClass::class);
        self::assertInstanceOf(NameOfExistingClass::class, $m);
    }
}
