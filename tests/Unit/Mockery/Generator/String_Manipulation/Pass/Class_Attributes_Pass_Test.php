<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Class_Attributes_Pass;
use Mockery\Generator\Undefined_Target_Class;

/**
 * @coversDefaultClass \Mockery
 */
class Class_Attributes_Pass_Test extends Mockery_Test_Case
{
    public const CODE = 'namespace Mockery; class Mock {}';

    /**
     * @see testCanApplyClassAttributes
     */
    public static function provideCanApplyClassAttributesCases(): iterable
    {
        yield 'has no attributes' => [
            'attributes' => [],
            'expected' => '',
        ];

        yield 'has one attribute' => [
            'attributes' => ['Attribute1'],
            'expected' => '#[Attribute1]',
        ];

        yield 'has attributes' => [
            'attributes' => ['Attribute1', 'Attribute2', 'Attribute3()'],
            'expected' => '#[Attribute1,Attribute2,Attribute3()]',
        ];
    }

    /**
     * @dataProvider provideCanApplyClassAttributesCases
     */
    public function testCanApplyClassAttributes(array $attributes, string $expected): void
    {
        $undefinedTargetClass = \mock(Undefined_Target_Class::class);
        $undefinedTargetClass->expects('get_attributes')
            ->once()
            ->and_return($attributes);

        $config = \mock(Mock_Configuration::class);
        $config->expects('get_target_class')
            ->once()
            ->and_return($undefinedTargetClass);

        $pass = new Class_Attributes_Pass();

        $code = $pass->apply(\file_get_contents(__FILE__), $config);

        self::assertStringContainsString($expected, $code);
    }
}
