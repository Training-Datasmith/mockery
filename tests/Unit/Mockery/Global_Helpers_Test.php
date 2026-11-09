<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Matcher\AndAnyOtherArgs;
use Mockery\Matcher\AnyArgs;
use Mockery\Mock_Interface;
use Throwable;

/**
 * @coversDefaultClass \Mockery
 */
final class GlobalHelpersTest extends Mockery_Test_Case
{
    /**
     * @throws Throwable
     */
    public function testAndAnyOtherArgs(): void
    {
        self::assertInstanceOf(AndAnyOtherArgs::class, \andAnyOtherArgs());
    }

    /**
     * @throws Throwable
     */
    public function testAndAnyOthers(): void
    {
        self::assertInstanceOf(AndAnyOtherArgs::class, \andAnyOthers());
    }

    /**
     * @throws Throwable
     */
    public function testAnyArgs(): void
    {
        self::assertInstanceOf(AnyArgs::class, \anyArgs());
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

        $double = \namedMock($className);

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
