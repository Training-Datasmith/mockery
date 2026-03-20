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

use function array_map;
use function in_array;
use Mockery\Generator\Mock_Configuration;
use function preg_replace;
use function sprintf;
use function str_replace;
class Avoid_Method_Clash_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $names = array_map(static function (\Mockery\Generator\Method $method) {
            return $method->get_name();
        }, $config->get_methods_to_mock());
        foreach (['allows', 'expects'] as $method) {
            if (in_array($method, $names, true)) {
                $code = preg_replace(sprintf('#// start method %s.*// end method %s#ms', $method, $method), '', $code);
                $code = str_replace(' implements MockInterface', ' implements LegacyMockInterface', $code);
            }
        }
        return $code;
    }
}