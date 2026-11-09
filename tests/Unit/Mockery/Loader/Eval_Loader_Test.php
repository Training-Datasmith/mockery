<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Loader;

use Mockery\Loader\Eval_Loader;
use Mockery\Loader\Loader;

/**
 * @coversDefaultClass \Mockery
 */
final class Eval_Loader_Test extends Loader_Test_Case
{
    public function getLoader(): Loader
    {
        return new Eval_Loader();
    }
}
