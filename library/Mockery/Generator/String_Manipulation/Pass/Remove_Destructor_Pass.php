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
use function preg_replace;
/**
 * Remove mock's empty destructor if we tend to use original class destructor
 */
class Remove_Destructor_Pass implements Pass
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
        if (!$config->is_mock_original_destructor()) {
            return preg_replace('/public function __destruct\(\)\s+\{.*?\}/sm', '', $code);
        }
        return $code;
    }
}