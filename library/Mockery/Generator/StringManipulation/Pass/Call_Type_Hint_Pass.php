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

use Mockery\Generator\Mock_Configuration;
use function str_replace;
class Call_Type_Hint_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        if ($config->requires_call_type_hint_removal()) {
            $code = str_replace('public function __call($method, array $args)', 'public function __call($method, $args)', $code);
        }
        if ($config->requires_call_static_type_hint_removal()) {
            return str_replace('public static function __callStatic($method, array $args)', 'public static function __callStatic($method, $args)', $code);
        }
        return $code;
    }
}