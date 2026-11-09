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
use function implode;
use function ltrim;
use Mockery\Generator\Mock_Configuration;
use function preg_replace;
class Trait_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $traits = $config->get_target_traits();
        if ($traits === []) {
            return $code;
        }
        $use_statements = array_map(static function ($trait): string {
            return 'use \\\\' . ltrim($trait->get_name(), '\\') . ';';
        }, $traits);
        return preg_replace('/^{$/m', "{\n    " . implode("\n    ", $use_statements) . "\n", $code);
    }
}