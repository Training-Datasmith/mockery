<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Constants_Pass;
use PHP73\ClassWithConstants;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Constants_Pass_Test extends TestCase
{
    public const CODE = 'class Foo {}';

    public function testShouldAddConstants(): void
    {
        $pass = new Constants_Pass();

        $config = new Mock_Configuration(
            [],
            [],
            [],
            ClassWithConstants::class,
            false,
            [],
            false,
            [
                ClassWithConstants::class => [
                    'FOO' => 'test',
                ],
            ]
        );

        $code = $pass->apply(static::CODE, $config);

        self::assertNotFalse(\mb_strpos($code, "const FOO = 'test'"));
    }
}
