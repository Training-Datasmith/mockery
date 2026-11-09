<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Call_Type_Hint_Pass;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Call_Type_Hint_Pass_Test extends TestCase
{
    public const CODE = ' public function __call($method, array $args) {}
                   public static function __callStatic($method, array $args) {}
    ';

    public function testShouldRemoveCallStaticTypeHintIfRequired(): void
    {
        $pass = new Call_Type_Hint_Pass();
        $config = Mockery::mock(Mock_Configuration::class, [
            'requires_call_static_type_hint_removal' => true,
        ])->make_partial();
        $code = $pass->apply(self::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, '__callStatic($method, $args)'));
    }

    public function testShouldRemoveCallTypeHintIfRequired(): void
    {
        $pass = new Call_Type_Hint_Pass();
        $config = Mockery::mock(Mock_Configuration::class, [
            'requires_call_type_hint_removal' => true,
        ])->make_partial();
        $code = $pass->apply(self::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, '__call($method, $args)'));
    }
}
