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

use function implode;
use Mockery\Generator\Mock_Configuration;
use function str_replace;
class Class_Attributes_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $class = $config->get_target_class();
        if (!$class) {
            return $code;
        }
        /** @var array<string> $attributes */
        $attributes = $class->get_attributes();
        if ($attributes !== []) {
            return str_replace('#[\AllowDynamicProperties]', '#[' . implode(',', $attributes) . ']', $code);
        }
        return $code;
    }
}