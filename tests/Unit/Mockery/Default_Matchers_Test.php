<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use DateTime;
use Hamcrest\Core\IsEqual;
use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\CustomValueObject;
use PHP73\CustomValueObjectInterface;
use PHP73\CustomValueObjectMatcher;

/**
 * @coversDefaultClass \Mockery
 */
final class Default_Matchers_Test extends Mockery_Test_Case
{
    protected $mock;

    protected function mockery_test_set_up(): void
    {
        $this->mock = \mock('foo');
    }

    public function mockery_test_tear_down(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testDefaultMatcherClass(): void
    {
        Mockery::get_configuration()->set_default_matcher(CustomValueObject::class, CustomValueObjectMatcher::class);
        $this->mock->should_receive('foo')
            ->with(new CustomValueObject('expected'))
            ->once();
        $this->mock->foo(new CustomValueObject('expected'));
    }

    /**
     * Just a quickie roundup of a few Hamcrest matchers to check nothing obvious out of place *
     */
    public function testDefaultMatcherHamcrest(): void
    {
        Mockery::get_configuration()->set_default_matcher(DateTime::class, IsEqual::class);
        $this->mock->should_receive('foo')
            ->with(new DateTime('2000-01-01'))
            ->once();
        $this->mock->foo(new DateTime('2000-01-01'));
    }

    public function testDefaultMatcherInterface(): void
    {
        Mockery::get_configuration()->set_default_matcher(
            CustomValueObjectInterface::class,
            CustomValueObjectMatcher::class
        );
        $this->mock->should_receive('foo')
            ->with(new CustomValueObject('expected2'))
            ->once();
        $this->mock->foo(new CustomValueObject('expected2'));
    }
}
