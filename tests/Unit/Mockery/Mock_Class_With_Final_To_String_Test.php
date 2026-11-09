<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Container;
use PHP73\SubclassWithFinalToString;
use PHP73\TestWithFinalToString;
use PHP73\TestWithNonFinalToString;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Class_With_Final_To_String_Test extends Mockery_Test_Case
{
    protected $container;

    protected function mockery_test_set_up(): void
    {
        $this->container = new Container();
    }

    protected function mockery_test_tear_down(): void
    {
        $this->container->mockery_close();
    }

    /**
     * Test that we are able to create partial mocks of classes that have
     * a __wakeup method marked as final. As long as __wakeup is not one of the
     * mocked methods.
     */
    public function testCreateMockForClassWithFinalToString(): void
    {
        $mock = $this->container->mock(TestWithFinalToString::class);
        self::assertInstanceOf(TestWithFinalToString::class, $mock);
        self::assertSame(TestWithFinalToString::class . '::__toString', $mock->__toString());

        $mock = $this->container->mock(SubclassWithFinalToString::class);
        self::assertInstanceOf(TestWithFinalToString::class, $mock);
        self::assertSame(TestWithFinalToString::class . '::__toString', $mock->__toString());
    }

    public function testCreateMockForClassWithNonFinalToString(): void
    {
        $mock = $this->container->mock(TestWithNonFinalToString::class);
        self::assertInstanceOf(TestWithNonFinalToString::class, $mock);

        // Make sure __toString is overridden.
        self::assertNotSame('bar', $mock->__toString());
    }
}
