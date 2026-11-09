<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\Invalid_Count_Exception;
use PHP73\ClassWithAllowsMethod;
use PHP73\ClassWithExpectsMethod;

/**
 * @coversDefaultClass \Mockery
 */
final class Allows_Expects_Syntax_Test extends Mockery_Test_Case
{
    public function testAllowsCanTakeAString(): void
    {
        $stub = Mockery::mock();
        $stub->allows('foo')
            ->and_returns('bar');
        self::assertSame('bar', $stub->foo());
    }

    public function testAllowsCanTakeAnArrayOfCalls(): void
    {
        $stub = Mockery::mock();
        $stub->allows([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        self::assertSame('bar', $stub->foo());
        self::assertSame('baz', $stub->bar());
    }

    public function testAllowsSetsUpMethodStub(): void
    {
        $stub = Mockery::mock();
        $stub->allows()
            ->foo(123)
            ->and_returns(456);

        self::assertSame(456, $stub->foo(123));
    }

    public function testCallVerificationCountCanBeOverridenAfterExpects(): void
    {
        $mock = Mockery::mock();
        $mock->expects()
            ->foo(123)
            ->twice();

        $mock->foo(123);
        $mock->foo(123);
    }

    public function testCallVerificationCountCanBeOverridenAfterExpectsThrowsExceptionWhenIncorrectNumberOfCalls(): void
    {
        $mock = Mockery::mock();
        $mock->expects()
            ->foo(123)
            ->twice();

        $mock->foo(123);
        $this->expectException(\Mockery\Exception\Invalid_Count_Exception::class);
        Mockery::close();
    }

    public function testExpectsCanOptionallyMatchOnAnyArguments(): void
    {
        $mock = Mockery::mock();
        $mock->allows()
            ->foo()
            ->with_any_args()
            ->and_returns(123);

        self::assertSame(123, $mock->foo(456, 789));
    }

    public function testExpectsCanTakeAString(): void
    {
        $mock = Mockery::mock();
        $mock->expects('foo')
            ->and_returns(123);

        self::assertSame(123, $mock->foo(456, 789));
    }

    public function testExpectsSetsUpExpectationOfOneCall(): void
    {
        $mock = Mockery::mock();
        $mock->expects()
            ->foo(123);

        $this->expectException(Invalid_Count_Exception::class);
        Mockery::close();
    }

    public function testGenerateSkipsAllowsMethodIfAlreadyExists(): void
    {
        $stub = Mockery::mock(ClassWithAllowsMethod::class);

        $stub->should_receive('allows')
            ->and_return(123);

        self::assertSame(123, $stub->allows());
    }

    public function testGenerateSkipsExpectsMethodIfAlreadyExists(): void
    {
        $stub = Mockery::mock(ClassWithExpectsMethod::class);

        $stub->should_receive('expects')
            ->and_return(123);

        self::assertSame(123, $stub->expects());
    }
}
