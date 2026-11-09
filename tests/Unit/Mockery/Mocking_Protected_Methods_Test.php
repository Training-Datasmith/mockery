<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use function mock;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\TestIncreasedVisibilityChild;

use PHP73\TestWithProtectedMethods;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Protected_Methods_Test extends Mockery_Test_Case
{
    public function testShouldAllowMockingAbstractProtectedMethods(): void
    {
        $mock = \mock(TestWithProtectedMethods::class)
            ->make_partial()
            ->should_allow_mocking_protected_methods();

        $mock->should_receive('abstractProtected')
            ->and_return('abstractProtected');
        self::assertSame('abstractProtected', $mock->foo());
    }

    public function testShouldAllowMockingIncreasedVisabilityMethods(): void
    {
        $mock = \mock(TestIncreasedVisibilityChild::class);
        $mock->should_receive('foobar')
            ->and_return('foobar');
        self::assertSame('foobar', $mock->foobar());
    }

    public function testShouldAllowMockingProtectedMethodOnDefinitionTimePartial(): void
    {
        $mock = \mock(TestWithProtectedMethods::class . '[protectedBar]')
            ->should_allow_mocking_protected_methods();

        $mock->should_receive('protectedBar')
            ->and_return('notbar');
        self::assertSame('notbar', $mock->bar());
    }

    public function testShouldAllowMockingProtectedMethods(): void
    {
        $mock = \mock(TestWithProtectedMethods::class)
            ->make_partial()
            ->should_allow_mocking_protected_methods();

        $mock->should_receive('protectedBar')
            ->and_return('notbar');
        self::assertSame('notbar', $mock->bar());
    }

    /**
     * This is a regression test, basically we don't want the mock handling
     * interfering with calling protected methods partials
     */
    public function testShouldAutomaticallyDeferCallsToProtectedMethodsForPartials(): void
    {
        $mock = \mock(TestWithProtectedMethods::class . '[foo]');

        self::assertSame('bar', $mock->bar());
    }

    /**
     * This is a regression test, basically we don't want the mock handling
     * interfering with calling protected methods partials
     */
    public function testShouldAutomaticallyDeferCallsToProtectedMethodsForRuntimePartials(): void
    {
        $mock = \mock(TestWithProtectedMethods::class)->make_partial();
        self::assertSame('bar', $mock->bar());
    }

    public function testShouldAutomaticallyIgnoreAbstractProtectedMethods(): void
    {
        $mock = \mock(TestWithProtectedMethods::class)->make_partial();
        self::assertNull($mock->foo());
    }
}
