<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\Invalid_Count_Exception;

/**
 * @coversDefaultClass \Mockery
 */
final class Callable_Spy_Test extends Mockery_Test_Case
{
    public function testItActsAsPartial(): void
    {
        $spy = \spy(function ($number) {
            return $number + 1;
        });

        self::assertSame(124, $spy(123));
        $spy->should_have_been_called();
    }

    public function testItCanVerifyItWasCalledANumberOfTimes(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();
        $spy();

        $spy->should_have_been_called()
            ->twice();
    }

    public function testItCanVerifyItWasCalledANumberOfTimesWithParticularArguments(): void
    {
        $spy = \spy(function (): void {
        });

        $spy(123);
        $spy(123);

        $spy->should_have_been_called()
            ->with(123)
            ->twice();
    }

    public function testItThrowsIfItWasCalledLessThanTheNumberOfTimesWeExpected(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->twice();
    }

    public function testItThrowsIfItWasCalledLessThanTheNumberOfTimesWeExpectedWithParticularArguments(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();
        $spy(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->with(123)
            ->twice();
    }

    public function testItThrowsIfItWasCalledMoreThanTheNumberOfTimesWeExpected(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();
        $spy();
        $spy();

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->twice();
    }

    public function testItThrowsIfItWasCalledMoreThanTheNumberOfTimesWeExpectedWithParticularArguments(): void
    {
        $spy = \spy(function (): void {
        });

        $spy(123);
        $spy(123);
        $spy(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->with(123)
            ->twice();
    }

    public function testItThrowsIfItWasCalledWhenWeExpectedItToNotHaveBeenCalled(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_not_have_been_called();
    }

    public function testItThrowsIfItWasCalledWithTheArgsWeWereNotExpecting(): void
    {
        $spy = \spy(function (): void {
        });

        $spy(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_not_have_been_called([123]);
    }

    public function testItThrowsIfTheArgumentsDoNotMatch(): void
    {
        $spy = \spy(function (): void {
        });

        $spy(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->with(123, 546);
    }

    public function testItThrowsIfTheCallableWasNotCalledAtAll(): void
    {
        $spy = \spy(function (): void {
        });

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called();
    }

    public function testItThrowsIfThereWereNoArgumentsButWeExpectedSome(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_been_called()
            ->with(123, 546);
    }

    public function testItVerifiesItWasNotCalledWithSomeParticularArgumentsWhenCalledWithDifferentArgs(): void
    {
        $spy = \spy(function (): void {
        });

        $spy(456);

        $spy->should_not_have_been_called([123]);
    }

    public function testItVerifiesItWasNotCalledWithSomeParticularArgumentsWhenCalledWithNoArgs(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();

        $spy->should_not_have_been_called([123]);
    }

    public function testItVerifiesTheClosureWasCalled(): void
    {
        $spy = \spy(function (): void {
        });

        $spy();

        $spy->should_have_been_called();
    }

    public function testItVerifiesTheClosureWasNotCalled(): void
    {
        $spy = \spy(function (): void {
        });

        $spy->should_not_have_been_called();
    }
}
