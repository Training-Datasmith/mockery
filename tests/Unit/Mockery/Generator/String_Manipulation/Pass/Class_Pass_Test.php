<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Class_Pass;

/**
 * @coversDefaultClass \Mockery
 */
final class Class_Pass_Test extends Mockery_Test_Case
{
    public const CODE = 'class Mock implements \\Mockery\\Mock_Interface {}';

    protected $pass;

    protected function mockery_test_set_up(): void
    {
        $this->pass = new Class_Pass();
    }

    public function testShouldDeclareUnknownClass(): void
    {
        $config = new Mock_Configuration(['Testing\TestClass'], [], [], 'Dave\Dave');

        $code = $this->pass->apply(self::CODE, $config);

        self::assertNotFalse(\mb_strpos($code, 'class Mock extends \Testing\TestClass implements \\Mockery\\Mock_Interface'));
    }
}
