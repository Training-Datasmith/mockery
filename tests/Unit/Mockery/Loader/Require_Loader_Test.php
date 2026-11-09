<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Loader;

use Mockery\Loader\Loader;
use Mockery\Loader\Require_Loader;

/**
 * @coversDefaultClass \Mockery
 */
final class Require_Loader_Test extends Loader_Test_Case
{
    public function getLoader(): Loader
    {
        return new Require_Loader(\sys_get_temp_dir());
    }
}
