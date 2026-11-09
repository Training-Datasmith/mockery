<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Loader;

use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\Mock_Definition;
use Mockery\Loader\Loader;
use PHPUnit\Framework\TestCase;

abstract class Loader_Test_Case extends TestCase
{
    abstract public function getLoader(): Loader;

    public function testLoad(): void
    {
        $className = \uniqid('Mock_', false);

        $config = new Mock_Configuration([], [], [], $className);

        $code = "<?php class {$className} { } ";

        $definition = new Mock_Definition($config, $code);

        $this->getLoader()
            ->load($definition);

        self::assertTrue(\class_exists($className));
    }
}
