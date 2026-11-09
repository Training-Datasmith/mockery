<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\StringManipulation\Pass;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Generator\MockConfiguration;
use Mockery\Generator\StringManipulation\Pass\ClassNamePass;

/**
 * @coversDefaultClass \Mockery
 */
final class ClassNamePassTest extends Mockery_Test_Case
{
    public const CODE = 'namespace Mockery; class Mock {}';

    protected $pass;

    protected function mockery_test_set_up(): void
    {
        $this->pass = new ClassNamePass();
    }

    public function testShouldRemoveLeadingBackslashesFromNamespace(): void
    {
        $config = new MockConfiguration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(static::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, 'namespace Dave;'));
    }

    public function testShouldRemoveNamespaceDefinition(): void
    {
        $config = new MockConfiguration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(self::CODE, $config);

        self::assertFalse(\mb_strpos($code, 'namespace Mockery;'));
    }

    public function testShouldReplaceClassNameWithSpecifiedName(): void
    {
        $config = new MockConfiguration([], [], [], 'Dave');
        $code = $this->pass->apply(static::CODE, $config);
        self::assertNotFalse(\mb_strpos($code, 'class Dave'));
    }

    public function testShouldReplaceNamespaceIfClassNameIsNamespaced(): void
    {
        $config = new MockConfiguration([], [], [], 'Dave\Dave');
        $code = $this->pass->apply(self::CODE, $config);
        self::assertFalse(\mb_strpos($code, 'namespace Mockery;'));
        self::assertNotFalse(\mb_strpos($code, 'namespace Dave;'));
    }
}
