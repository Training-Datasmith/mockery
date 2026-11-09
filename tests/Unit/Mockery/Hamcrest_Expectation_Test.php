<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception;

/**
 * @coversDefaultClass \Mockery
 */
final class HamcrestExpectationTest extends Mockery_Test_Case
{
    protected $mock;

    protected function mockery_test_set_up(): void
    {
        $this->mock = \mock('foo');
    }

    public function mockery_test_tear_down(): void
    {
        Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
    }

    public function testAnythingConstraintMatchesArgument(): void
    {
        $this->mock->shouldReceive('foo')
            ->with(\anything())
            ->once();
        $this->mock->foo(2);
    }

    public function testGreaterThanConstraintMatchesArgument(): void
    {
        $this->mock->shouldReceive('foo')
            ->with(\greaterThan(1))
            ->once();
        $this->mock->foo(2);
    }

    public function testGreaterThanConstraintNotMatchesArgument(): void
    {
        $this->mock->shouldReceive('foo')
            ->with(\greaterThan(1));
        $this->expectException(Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }
}
