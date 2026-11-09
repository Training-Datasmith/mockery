<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator;

use Mockery\Generator\Mock_Configuration_Builder;
use PHP73\ClassWithDebugInfo;
use PHP73\ClassWithMagicCall;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Mock_Configuration_Builder_Test extends TestCase
{
    public function testMagicMethodsAreBlackListedByDefault(): void
    {
        $builder = new Mock_Configuration_Builder();
        $builder->add_target(ClassWithMagicCall::class);
        $methods = $builder->get_mock_configuration()
            ->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('foo', $methods[0]->get_name());
    }

    public function testReservedWordsAreBlackListedByDefault(): void
    {
        $builder = new Mock_Configuration_Builder();

        self::assertContains('__halt_compiler', $builder->get_mock_configuration()->get_black_listed_methods());

        // need a builtin for this
        self::markTestSkipped('Need a builtin class with a method that is a reserved word');
    }

    public function testXDebugsDebugInfoIsBlackListedByDefault(): void
    {
        $builder = new Mock_Configuration_Builder();
        $builder->add_target(ClassWithDebugInfo::class);
        $methods = $builder->get_mock_configuration()
            ->get_methods_to_mock();
        self::assertCount(1, $methods);
        self::assertSame('foo', $methods[0]->get_name());
    }
}
