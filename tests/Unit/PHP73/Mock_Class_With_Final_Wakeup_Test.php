<?php

declare(strict_types=1);

namespace Tests\Unit\PHP73;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\SubclassWithFinalWakeup;
use PHP73\TestWithFinalWakeup;
use PHP73\TestWithNonFinalWakeup;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Class_With_Final_Wakeup_Test extends Mockery_Test_Case
{
    protected $container;

    protected function mockery_test_set_up(): void
    {
        $this->container = new \Mockery\Container();
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
    public function testCreateMockForClassWithFinalWakeup(): void
    {
        $mock = $this->container->mock(TestWithFinalWakeup::class);
        self::assertInstanceOf(TestWithFinalWakeup::class, $mock);
        self::assertSame(TestWithFinalWakeup::class . '::__wakeup', $mock->__wakeup());

        $mock = $this->container->mock(SubclassWithFinalWakeup::class);
        self::assertInstanceOf(SubclassWithFinalWakeup::class, $mock);
        self::assertSame(TestWithFinalWakeup::class . '::__wakeup', $mock->__wakeup());
    }

    public function testCreateMockForClassWithNonFinalWakeup(): void
    {
        $mock = $this->container->mock(TestWithNonFinalWakeup::class);
        self::assertInstanceOf(TestWithNonFinalWakeup::class, $mock);

        // Make sure __wakeup is overridden and doesn't return anything.
        self::assertNull($mock->__wakeup());
    }
}
