<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Exception\Bad_Method_Call_Exception;
use ErrorException;
use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception as MockeryException;
use Mockery\Mock;
use Mockery\Mock_Interface;
use PHP73\ClassWithMethods;
use PHP73\ClassWithNoToString;
use PHP73\ClassWithProtectedMethod;
use PHP73\ClassWithToString;
use PHP73\ExampleClassForTestingNonExistentMethod;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Test extends Mockery_Test_Case
{
    public function testAnonymousMockWorksWithNotAllowingMockingOfNonExistentMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);

        $m = \mock();
        $m->should_receive('test123')
            ->and_return(true);
        self::assertTrue($m->test123());

        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testCallingShouldReceiveWithoutAValidMethodName(): void
    {
        $mock = Mockery::mock();

        $this->expectException(InvalidArgumentException::class, 'Received empty method name');
        $mock->should_receive('');
    }

    public function testCanMockException(): void
    {
        $exception = Mockery::mock(Exception::class);
        self::assertInstanceOf(Exception::class, $exception);
    }

    public function testCanMockSubclassOfException(): void
    {
        $errorException = Mockery::mock(ErrorException::class);
        self::assertInstanceOf(ErrorException::class, $errorException);
        self::assertInstanceOf(Exception::class, $errorException);
    }

    public function testExpectationCountWillCountDefaultsIfNotOverriden(): void
    {
        $mock = new Mock();
        $mock->should_receive('doThis')
            ->once()
            ->by_default();
        $mock->should_receive('doThat')
            ->once()
            ->by_default();

        self::assertSame(2, $mock->mockery_get_expectation_count());
    }

    public function testExpectationCountWillCountExpectations(): void
    {
        $mock = new Mock();
        $mock->should_receive('doThis')
            ->once();
        $mock->should_receive('doThat')
            ->once();

        self::assertSame(2, $mock->mockery_get_expectation_count());
    }

    public function testExpectationCountWillIgnoreDefaultsIfOverriden(): void
    {
        $mock = new Mock();
        $mock->should_receive('doThis')
            ->once()
            ->by_default();
        $mock->should_receive('doThis')
            ->twice();
        $mock->should_receive('andThis')
            ->twice();

        self::assertSame(2, $mock->mockery_get_expectation_count());
    }

    public function testMockAddsToString(): void
    {
        $mock = \mock(ClassWithNoToString::class);
        self::assertTrue(\method_exists($mock, '__toString'));
    }

    public function testMockToStringMayBeDeferred(): void
    {
        $mock = \mock(ClassWithToString::class)->make_partial();
        self::assertSame('foo', (string) $mock);
    }

    public function testMockToStringShouldIgnoreMissingAlwaysReturnsString(): void
    {
        $mock = \mock(ClassWithNoToString::class)->should_ignore_missing();
        self::assertNotSame('', (string) $mock);

        $mock->as_undefined();
        self::assertNotSame('', (string) $mock);
    }

    public function testMockWithNotAllowingMockingOfNonExistentMethodsCanBeGivenAdditionalMethodsToMockEvenIfTheyDontExistOnClass(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $m = \mock(ExampleClassForTestingNonExistentMethod::class);
        $m->should_allow_mocking_method('testSomeNonExistentMethod');
        $m->should_receive('testSomeNonExistentMethod')
            ->and_return(true)
            ->once();
        self::assertTrue($m->testSomeNonExistentMethod());
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testProtectedMethodMockWithNotAllowingMockingOfNonExistentMethodsWhenShouldAllowMockingProtectedMethodsIsCalled(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $m = \mock(ClassWithProtectedMethod::class);
        $m->should_allow_mocking_protected_methods();
        $m->should_receive('foo')
            ->and_return(true);
        self::assertTrue($m->foo());
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testShouldAllowMockingMethodReturnsMockInstance(): void
    {
        $m = Mockery::mock('someClass');
        self::assertInstanceOf(Mock_Interface::class, $m->should_allow_mocking_method('testFunction'));
    }

    public function testShouldAllowMockingProtectedMethodReturnsMockInstance(): void
    {
        $m = Mockery::mock('someClass');
        self::assertInstanceOf(Mock_Interface::class, $m->should_allow_mocking_protected_methods('testFunction'));
    }

    public function testShouldIgnoreMissing(): void
    {
        $mock = \mock(ClassWithNoToString::class)->should_ignore_missing();
        self::assertNull($mock->nonExistingMethod());
    }

    public function testShouldIgnoreMissingCallingExistentMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(ClassWithMethods::class)->should_ignore_missing();

        self::assertNull($mock->foo());

        $mock->should_receive('bar')
            ->passthru();

        self::assertSame('bar', $mock->bar());
    }

    public function testShouldIgnoreMissingCallingNonExistentMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
        $mock = \mock(ClassWithMethods::class)->should_ignore_missing();

        self::assertNull($mock->foo());
        self::assertNull($mock->bar());
        self::assertNull($mock->nonExistentMethod());

        $mock->should_receive([
            'foo' => 'new_foo',
            'nonExistentMethod' => 'result',
        ]);
        $mock->should_receive('bar')
            ->passthru();

        self::assertSame('new_foo', $mock->foo());
        self::assertSame('bar', $mock->bar());
        self::assertSame('result', $mock->nonExistentMethod());
    }

    public function testShouldIgnoreMissingCallingNonExistentMethodsUsingGlobalConfiguration(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(ClassWithMethods::class)->should_ignore_missing();
        $this->expectException(Bad_Method_Call_Exception::class);
        $mock->nonExistentMethod();
    }

    public function testShouldIgnoreMissingDisallow_mocking_non_existent_methodsUsingGlobalConfiguration(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(ClassWithMethods::class)->should_ignore_missing();
        $this->expectException(MockeryException::class);
        $mock->should_receive('nonExistentMethod');
    }

    public function testShouldThrowExceptionWithInvalidClassName(): void
    {
        $this->expectException(MockeryException::class);
        \mock('ClassName.CannotContainDot');
    }
}
