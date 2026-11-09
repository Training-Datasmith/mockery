<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\Invalid_Count_Exception;

/**
 * @coversDefaultClass \Mockery
 */
final class Spy_Test extends Mockery_Test_Case
{
    public function testAnyArgsCanBeUsedWithAlternativeSyntax(): void
    {
        $spy = Mockery::spy();
        $spy->foo(123, 456);

        $spy->should_have_received()
            ->foo(\any_args());
    }

    public function testItIncrementsExpectationCountWhenShouldHaveReceivedIsUsed(): void
    {
        $spy = Mockery::spy();
        $spy->myMethod('param1', 'param2');
        $spy->should_have_received('myMethod')
            ->with('param1', 'param2');
        self::assertSame(1, $spy->mockery_get_expectation_count());
    }

    public function testItIncrementsExpectationCountWhenShouldNotHaveReceivedIsUsed(): void
    {
        $spy = Mockery::spy();
        $spy->should_not_have_received('method');
        self::assertSame(1, $spy->mockery_get_expectation_count());
    }

    public function testItVerifiesAMethodWasCalled(): void
    {
        $spy = Mockery::spy();
        $spy->myMethod();
        $spy->should_have_received('myMethod');

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_received('someMethodThatWasNotCalled');
    }

    public function testItVerifiesAMethodWasCalledASpecificNumberOfTimes(): void
    {
        $spy = Mockery::spy();
        $spy->myMethod();
        $spy->myMethod();
        $spy->should_have_received('myMethod')
            ->twice();

        $this->expectException(Invalid_Count_Exception::class);
        $spy->myMethod();
        $spy->should_have_received('myMethod')
            ->twice();
    }

    public function testItVerifiesAMethodWasCalledWithSpecificArguments(): void
    {
        $spy = Mockery::spy();
        $spy->myMethod(123, 'a string');
        $spy->should_have_received('myMethod')
            ->with(123, 'a string');
        $spy->should_have_received('myMethod', [123, 'a string']);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_received('myMethod')
            ->with(123);
    }

    public function testItVerifiesAMethodWasNotCalled(): void
    {
        $spy = Mockery::spy();
        $spy->should_not_have_received('myMethod');

        $this->expectException(Invalid_Count_Exception::class);
        $spy->myMethod();
        $spy->should_not_have_received('myMethod');
    }

    public function testItVerifiesAMethodWasNotCalledWithParticularArguments(): void
    {
        $spy = Mockery::spy();
        $spy->myMethod(123, 456);

        $spy->should_not_have_received('myMethod', [789, 10]);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_not_have_received('myMethod', [123, 456]);
    }

    public function testShouldHaveReceivedHigherOrderMessageCallAMethodWithCorrectArguments(): void
    {
        $spy = Mockery::spy();
        $spy->foo(123);

        $spy->should_have_received()
            ->foo(123);
    }

    public function testShouldHaveReceivedHigherOrderMessageCallAMethodWithIncorrectArgumentsThrowsException(): void
    {
        $spy = Mockery::spy();
        $spy->foo(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_have_received()
            ->foo(456);
    }

    public function testShouldNotHaveReceivedHigherOrderMessageCallAMethodWithCorrectArgumentsThrowsAnException(): void
    {
        $spy = Mockery::spy();
        $spy->foo(123);

        $this->expectException(Invalid_Count_Exception::class);
        $spy->should_not_have_received()
            ->foo(123);
    }

    public function testShouldNotHaveReceivedHigherOrderMessageCallAMethodWithIncorrectArguments(): void
    {
        $spy = Mockery::spy();
        $spy->foo(123);

        $spy->should_not_have_received()
            ->foo(456);
    }
}
