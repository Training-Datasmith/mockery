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

use function class_exists;
use Mockery\Generator\Mock_Definition;
class Eval_Loader implements Loader
{
    /**
     * Load the given mock definition
     */
    public function load(Mock_Definition $definition): void
    {
        if (class_exists($definition->get_class_name(), false)) {
            return;
        }
        eval('?>' . $definition->get_code());
    }
}