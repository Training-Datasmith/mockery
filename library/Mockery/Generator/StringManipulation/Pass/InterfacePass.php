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

use function array_reduce;
use function interface_exists;
use function ltrim;
use Mockery;
use Mockery\Generator\Mock_Configuration;
use function str_replace;
class Interface_Pass implements Pass
{
    /**
     * @param  string $code
     */
    public function apply($code, Mock_Configuration $config): string
    {
        foreach ($config->get_target_interfaces() as $i) {
            $name = ltrim($i->get_name(), '\\');
            if (!interface_exists($name)) {
                Mockery::declare_interface($name);
            }
        }
        $interfaces = array_reduce($config->get_target_interfaces(), static function (string $code, \Mockery\Generator\Target_Class_Interface $i): string {
            return $code . ', \\' . ltrim($i->get_name(), '\\');
        }, '');
        return str_replace('implements MockInterface', 'implements MockInterface' . $interfaces, $code);
    }
}