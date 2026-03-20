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

use function array_key_exists;
use Mockery\Generator\Mock_Configuration;
use const PHP_EOL;
use function sprintf;
use function strrpos;
use function substr_replace;
use function var_export;
class Constants_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $cm = $config->get_constants_map();
        if ($cm === []) {
            return $code;
        }
        $name = $config->get_name();
        if (!array_key_exists($name, $cm)) {
            return $code;
        }
        $constants_code = '';
        foreach ($cm[$name] as $constant => $value) {
            $constants_code .= sprintf("\n    const %s = %s;\n", $constant, var_export($value, true));
        }
        $offset = strrpos($code, '}');
        if ($offset === false) {
            return $code;
        }
        return substr_replace($code, $constants_code, $offset) . '}' . PHP_EOL;
    }
}