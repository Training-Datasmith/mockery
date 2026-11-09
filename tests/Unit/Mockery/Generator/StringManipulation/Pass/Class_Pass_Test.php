<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\StringManipulation\Pass;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Generator\MockConfiguration;
use Mockery\Generator\StringManipulation\Pass\ClassPass;

/**
 * @coversDefaultClass \Mockery
 */
final class ClassPassTest extends Mockery_Test_Case
{
    public const CODE = 'class Mock implements Mock_Interface {}';

    protected $pass;

    protected function mockery_test_set_up(): void
    {
        $this->pass = new ClassPass();
    }

    public function testShouldDeclareUnknownClass(): void
    {
        $config = new MockConfiguration(['Testing\TestClass'], [], [], 'Dave\Dave');

        $code = $this->pass->apply(self::CODE, $config);

        self::assertNotFalse(\mb_strpos($code, 'class Mock extends \Testing\TestClass implements Mock_Interface'));
    }
}
