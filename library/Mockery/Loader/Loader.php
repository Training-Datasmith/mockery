<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Loader;

use Mockery\Generator\Mock_Definition;
interface Loader
{
    /**
     * Load the given mock definition
     *
     * @return void
     */
    public function load(Mock_Definition $definition);
}