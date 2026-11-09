<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Matcher\And_Any_Other_Args;
use Mockery\Matcher\Any_Args;
use Mockery\Mock_Interface;
use Throwable;

/**
 * @coversDefaultClass \Mockery
 */
final class Global_Helpers_Test extends Mockery_Test_Case
{
    /**
     * @throws Throwable
     */
    public function testAndAnyOtherArgs(): void
    {
        self::assertInstanceOf(And_Any_Other_Args::class, \and_any_other_args());
    }

    /**
     * @throws Throwable
     */
    public function testAndAnyOthers(): void
    {
        self::assertInstanceOf(And_Any_Other_Args::class, \and_any_others());
    }

    /**
     * @throws Throwable
     */
    public function testAnyArgs(): void
    {
        self::assertInstanceOf(Any_Args::class, \any_args());
    }

    public function testMockCreatesAMock(): void
    {
        $double = \mock();

        self::assertInstanceOf(Mock_Interface::class, $double);

        $this->expectException(Throwable::class);

        $double->foo();
    }

    public function testNamedMockCreatesANamedMock(): void
    {
        $className = \uniqid('Class');

        $double = \named_mock($className);

        self::assertInstanceOf(Mock_Interface::class, $double);
        self::assertInstanceOf($className, $double);
    }

    public function testSpyCreatesASpy(): void
    {
        $double = \spy();

        self::assertInstanceOf(Mock_Interface::class, $double);
        $double->foo();
    }
}
