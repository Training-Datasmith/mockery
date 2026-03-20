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

use function array_filter;
use function array_merge;
use function end;
use function in_array;
use function is_array;
use Mockery\Generator\Method;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\Parameter;
use Mockery\Generator\Target_Class_Interface;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function sprintf;
class Magic_Method_Type_Hints_Pass implements Pass
{
    /**
     * @var array
     */
    private $mock_magic_methods = ['__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone', '__debugInfo'];
    /**
     * Apply implementation.
     *
     * @param string $code
     *
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $magic_methods = $this->get_magic_methods($config->get_target_class());
        foreach ($config->get_target_interfaces() as $interface) {
            $magic_methods = array_merge($magic_methods, $this->get_magic_methods($interface));
        }
        foreach ($magic_methods as $method) {
            $code = $this->apply_magic_type_hints($code, $method);
        }
        return $code;
    }
    /**
     * Returns the magic methods within the
     * passed DefinedTargetClass.
     */
    public function get_magic_methods(?Target_Class_Interface $class = null): array
    {
        if (!$class instanceof Target_Class_Interface) {
            return [];
        }
        return array_filter($class->get_methods(), function (Method $method): bool {
            return in_array($method->get_name(), $this->mock_magic_methods, true);
        });
    }
    protected function render_type_hint(Parameter $param): string
    {
        $type_hint = $param->get_type_hint();
        return $type_hint === null ? '' : sprintf('%s ', $type_hint);
    }
    /**
     * Applies type hints of magic methods from
     * class to the passed code.
     *
     * @param int $code
     *
     * @return string
     */
    private function apply_magic_type_hints($code, Method $method)
    {
        if ($this->is_method_within_code($code, $method)) {
            $named_parameters = $this->get_original_parameters($code, $method);
            $code = preg_replace($this->get_declaration_regex($method->get_name()), $this->get_method_declaration($method, $named_parameters), $code);
        }
        return $code;
    }
    /**
     * Returns a regex string used to match the
     * declaration of some method.
     *
     *
     */
    private function get_declaration_regex(string $method_name): string
    {
        return sprintf('/public\s+(?:static\s+)?function\s+%s\s*\(.*\)\s*(?=\{)/i', $method_name);
    }
    /**
     * Gets the declaration code, as a string, for the passed method.
     *
     *
     */
    private function get_method_declaration(Method $method, array $named_parameters): string
    {
        $declaration = 'public';
        $declaration .= $method->is_static() ? ' static' : '';
        $declaration .= ' function ' . $method->get_name() . '(';
        foreach ($method->get_parameters() as $index => $parameter) {
            $declaration .= $this->render_type_hint($parameter);
            $name = $named_parameters[$index] ?? $parameter->get_name();
            $declaration .= '$' . $name;
            $declaration .= ',';
        }
        $declaration = rtrim($declaration, ',');
        $declaration .= ') ';
        $return_type = $method->get_return_type();
        if ($return_type !== null) {
            $declaration .= sprintf(': %s', $return_type);
        }
        return $declaration;
    }
    /**
     * Returns the method original parameters, as they're
     * described in the $code string.
     *
     * @param int $code
     *
     * @return array
     */
    private function get_original_parameters($code, Method $method)
    {
        $matches = [];
        $parameter_matches = [];
        preg_match($this->get_declaration_regex($method->get_name()), $code, $matches);
        if ($matches !== []) {
            preg_match_all('/(?<=\$)(\w+)+/i', $matches[0], $parameter_matches);
        }
        $group_matches = end($parameter_matches);
        return is_array($group_matches) ? $group_matches : [$group_matches];
    }
    /**
     * Checks if the method is declared within code.
     *
     * @param int $code
     */
    private function is_method_within_code($code, Method $method): bool
    {
        return preg_match($this->get_declaration_regex($method->get_name()), $code) === 1;
    }
}