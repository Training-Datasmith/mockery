<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Interface_Pass;
use Mockery\Generator\Undefined_Target_Class;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Interface_Pass_Test extends TestCase
{
    public const CODE = 'class Mock implements \\Mockery\\Mock_Interface';

    public function testShouldAddAnyInterfaceNamesToImplementsDefinition(): void
    {
        $pass = new Interface_Pass();

        $config = Mockery::mock(Mock_Configuration::class);
        $config->should_receive('get_target_interfaces')->and_return([
            Undefined_Target_Class::factory('\Dave\Dave'),
            Undefined_Target_Class::factory('\Paddy\Paddy'),
        ]);

        $code = $pass->apply(self::CODE, $config);

        self::assertNotFalse(\mb_strpos($code, 'implements \\Mockery\\Mock_Interface, \Dave\Dave, \Paddy\Paddy'));
    }

    public function testShouldNotAlterCodeIfNoTargetInterfaces(): void
    {
        $pass = new Interface_Pass();

        $config = Mockery::mock(Mock_Configuration::class);
        $config->should_receive('get_target_interfaces')->and_return([]);

        $code = $pass->apply(self::CODE, $config);
        self::assertSame(self::CODE, $code);
    }
}
