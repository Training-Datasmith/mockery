<?php

declare(strict_types=1);

/**
 * Mockery
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/padraic/mockery/master/LICENSE
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to padraic@php.net so we can send you a copy immediately.
 *
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2010 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    http://github.com/padraic/mockery/blob/master/LICENSE New BSD License
 */

namespace Tests\Unit\Mockery;

use Iterator;
use IteratorAggregate;
use Mockery\Exception;
use Mockery\Generator\Mock_Configuration;
use PHP73\ClassWithFinalMethod;
use PHP73\TestFinal;
use PHP73\TestInterface;
use PHP73\TestInterface2;
use PHP73\TestSubject;
use PHP73\TestTraversableInterface;
use PHP73\TestTraversableInterface2;
use PHP73\TestTraversableInterface3;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @coversDefaultClass \Mockery
 */
class Mock_Configuration_Test extends TestCase
{
    public function testBlackListedMethodsShouldNotBeInListToBeMocked(): void
    {
        $config = new Mock_Configuration([TestSubject::class], ['foo']);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('bar', $methods[0]->get_name());
    }

    public function testBlackListsAreCaseInsensitive(): void
    {
        $config = new Mock_Configuration([TestSubject::class], ['FOO']);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('bar', $methods[0]->get_name());
    }

    public function testFinalMethodsAreExcluded(): void
    {
        $config = new Mock_Configuration([ClassWithFinalMethod::class]);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('bar', $methods[0]->get_name());
    }

    public function testOnlyWhiteListedMethodsShouldBeInListToBeMocked(): void
    {
        $config = new Mock_Configuration([TestSubject::class], [], ['foo']);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('foo', $methods[0]->get_name());
    }

    public function testShouldBringIteratorAggregateToHeadOfTargetListIfTraversablePresent(): void
    {
        $config = new Mock_Configuration([TestTraversableInterface3::class]);

        $interfaces = $config->get_target_interfaces();
        self::assertCount(2, $interfaces);
        self::assertSame(IteratorAggregate::class, $interfaces[0]->get_name());
        self::assertSame(TestTraversableInterface3::class, $interfaces[1]->get_name());
    }

    public function testShouldBringIteratorToHeadOfTargetListIfTraversablePresent(): void
    {
        $config = new Mock_Configuration([TestTraversableInterface2::class]);

        $interfaces = $config->get_target_interfaces();
        self::assertCount(2, $interfaces);
        self::assertSame(Iterator::class, $interfaces[0]->get_name());
        self::assertSame(TestTraversableInterface2::class, $interfaces[1]->get_name());
    }

    public function testShouldIncludeMethodsFromAllTargets(): void
    {
        $config = new Mock_Configuration([TestInterface::class, TestInterface2::class]);
        $methods = $config->get_methods_to_mock();
        self::assertCount(2, $methods);
    }

    public function testShouldTargetIteratorAggregateIfTraversableInTargetsTree(): void
    {
        $config = new Mock_Configuration([TestTraversableInterface::class]);

        $interfaces = $config->get_target_interfaces();
        self::assertCount(2, $interfaces);
        self::assertSame(IteratorAggregate::class, $interfaces[0]->get_name());
        self::assertSame(TestTraversableInterface::class, $interfaces[1]->get_name());
    }

    public function testShouldTargetIteratorAggregateIfTryingToMockTraversable(): void
    {
        $config = new Mock_Configuration([Traversable::class]);

        $interfaces = $config->get_target_interfaces();
        self::assertCount(1, $interfaces);
        $first = \array_shift($interfaces);
        self::assertSame(IteratorAggregate::class, $first->get_name());
    }

    public function testShouldThrowIfTargetClassIsFinal(): void
    {
        $this->expectException(Exception::class);
        $config = new Mock_Configuration([TestFinal::class]);
        $config->get_target_class();
    }

    public function testWhiteListsAreCaseInsensitive(): void
    {
        $config = new Mock_Configuration([TestSubject::class], [], ['FOO']);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('foo', $methods[0]->get_name());
    }

    public function testWhitelistOverRulesBlackList(): void
    {
        $config = new Mock_Configuration([TestSubject::class], ['foo'], ['foo']);

        $methods = $config->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('foo', $methods[0]->get_name());
    }
}
