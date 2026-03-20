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
use Mockery\Generator\Target_Class_Interface;
use function preg_replace;
/**
 * The standard Mockery\Mock class includes some methods to ease mocking, such
 * as __wakeup, however if the target has a final __wakeup method, it can't be
 * mocked. This pass removes the builtin methods where they are final on the
 * target
 */
class Remove_Builtin_Methods_That_Are_Final_Pass implements Pass
{
    protected $methods = ['__wakeup' => '/public function __wakeup\(\)\s+\{.*?\}/sm', '__toString' => '/public function __toString\(\)\s+(:\s+string)?\s*\{.*?\}/sm'];
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $target = $config->get_target_class();
        if (!$target instanceof Target_Class_Interface) {
            return $code;
        }
        foreach ($target->get_methods() as $method) {
            if (!$method->is_final()) {
                continue;
            }
            if (!isset($this->methods[$method->get_name()])) {
                continue;
            }
            $code = preg_replace($this->methods[$method->get_name()], '', $code);
        }
        return $code;
    }
}