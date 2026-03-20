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

use function ltrim;
use Mockery\Generator\Mock_Configuration;
use function str_replace;
class Class_Name_Pass implements Pass
{
    /**
     * @param  string $code
     */
    public function apply($code, Mock_Configuration $config): string
    {
        $namespace = $config->get_namespace_name();
        $namespace = ltrim($namespace, '\\');
        $class_name = $config->get_short_name();
        $code = str_replace('namespace Mockery;', $namespace !== '' ? 'namespace ' . $namespace . ';' : '', $code);
        return str_replace('class Mock', 'class ' . $class_name, $code);
    }
}