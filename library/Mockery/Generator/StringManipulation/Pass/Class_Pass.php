<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Generator\String_Manipulation\Pass;

use function class_exists;
use function ltrim;
use Mockery;
use Mockery\Generator\Mock_Configuration;
use function str_replace;
class Class_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $target = $config->get_target_class();
        if (!$target) {
            return $code;
        }
        if ($target->is_final()) {
            return $code;
        }
        $class_name = ltrim($target->get_name(), '\\');
        if (!class_exists($class_name)) {
            Mockery::declare_class($class_name);
        }
        return str_replace('implements MockInterface', 'extends \\' . $class_name . ' implements MockInterface', $code);
    }
}