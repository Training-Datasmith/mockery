<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\String_Manipulation\Pass\Class_Name_Pass;

/**
 * @coversDefaultClass \Mockery
 */
final class Class_Name_Pass_Test extends Mockery_Test_Case
{
    public const CODE = 'namespace Mockery; class Mock {}';

    protected $pass;

    protected function mockery_test_set_up(): void
    {
        $this->pass = new Class_Name_Pass();
    }

    public function testShouldRemoveLeadingBackslashesFromNamespace(): void
    {
        $config = new Mock_Configuration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(static::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, 'namespace Dave;'));
    }

    public function testShouldRemoveNamespaceDefinition(): void
    {
        $config = new Mock_Configuration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(self::CODE, $config);

        self::assertFalse(\mb_strpos($code, 'namespace Mockery;'));
    }

    public function testShouldReplaceClassNameWithSpecifiedName(): void
    {
        $config = new Mock_Configuration([], [], [], 'Dave');
        $code = $this->pass->apply(static::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, 'class Dave'));
    }

    public function testShouldReplaceNamespaceIfClassNameIsNamespaced(): void
    {
        $config = new Mock_Configuration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(self::CODE, $config);
        self::assertFalse(\mb_strpos($code, 'namespace Mockery;'));
        self::assertNotFalse(\mb_strpos($code, 'namespace Dave;'));
    }
}
