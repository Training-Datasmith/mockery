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

use function array_values;
use function count;
use function enum_exists;
use function get_class;
use function implode;
use function in_array;
use function is_object;
use Mockery\Generator\Method;
use Mockery\Generator\Mock_Configuration;
use Mockery\Generator\Parameter;
use const PHP_VERSION_ID;
use function preg_match;
use function sprintf;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function var_export;
class Method_Definition_Pass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        foreach ($config->get_methods_to_mock() as $method) {
            if ($method->is_public()) {
                $method_def = 'public';
            } elseif ($method->is_protected()) {
                $method_def = 'protected';
            } else {
                $method_def = 'private';
            }
            if ($method->is_static()) {
                $method_def .= ' static';
            }
            $method_def .= ' function ';
            $method_def .= $method->returns_reference() ? ' & ' : '';
            $method_def .= $method->get_name();
            $method_def .= $this->render_params($method, $config);
            $method_def .= $this->render_return_type($method);
            $method_def .= $this->render_method_body($method, $config);
            $code = $this->append_to_class($code, $method_def);
        }
        return $code;
    }
    protected function append_to_class($class, string $code): string
    {
        $last_brace = strrpos($class, '}');
        return substr($class, 0, $last_brace) . $code . "\n    }\n";
    }
    protected function render_params(Method $method, $config): string
    {
        $class = $method->get_declaring_class();
        if ($class->is_internal()) {
            $overrides = $config->get_parameter_overrides();
            if (isset($overrides[strtolower($class->get_name())][$method->get_name()])) {
                return '(' . implode(',', $overrides[strtolower($class->get_name())][$method->get_name()]) . ')';
            }
        }
        $method_params = [];
        $params = $method->get_parameters();
        $is_php81 = PHP_VERSION_ID >= 80100;
        foreach ($params as $param) {
            $param_def = $this->render_type_hint($param);
            $param_def .= $param->is_passed_by_reference() ? '&' : '';
            $param_def .= $param->is_variadic() ? '...' : '';
            $param_def .= '$' . $param->get_name();
            if (!$param->is_variadic()) {
                if ($param->is_default_value_available() !== false) {
                    $default_value = $param->get_default_value();
                    if (is_object($default_value)) {
                        $prefix = get_class($default_value);
                        if ($is_php81) {
                            if (enum_exists($prefix)) {
                                $prefix = var_export($default_value, true);
                            } elseif (!$param->is_default_value_constant() && preg_match('#<optional>\s.*?\s=\snew\s(.*?)\s]$#', $param->__toString(), $matches) === 1) {
                                $prefix = 'new ' . $matches[1];
                            }
                        }
                    } else {
                        $prefix = var_export($default_value, true);
                    }
                    $param_def .= ' = ' . $prefix;
                } elseif ($param->is_optional()) {
                    $param_def .= ' = null';
                }
            }
            $method_params[] = $param_def;
        }
        return '(' . implode(', ', $method_params) . ')';
    }
    protected function render_return_type(Method $method): string
    {
        $type = $method->get_return_type();
        return $type ? sprintf(': %s', $type) : '';
    }
    protected function render_type_hint(Parameter $param): string
    {
        $type_hint = $param->get_type_hint();
        return $type_hint === null ? '' : sprintf('%s ', $type_hint);
    }
    private function render_method_body($method, \Mockery\Generator\Mock_Configuration $config): string
    {
        $invoke = $method->is_static() ? 'static::_mockery_handleStaticMethodCall' : '$this->_mockery_handleMethodCall';
        $body = <<<BODY
        {
        \$argc = func_num_args();
        \$argv = func_get_args();
        
        BODY;
        // Fix up known parameters by reference - used func_get_args() above
        // in case more parameters are passed in than the function definition
        // says - eg varargs.
        $class = $method->get_declaring_class();
        $class_name = strtolower($class->get_name());
        $overrides = $config->get_parameter_overrides();
        if (isset($overrides[$class_name][$method->get_name()])) {
            $params = array_values($overrides[$class_name][$method->get_name()]);
            $param_count = count($params);
            for ($i = 0; $i < $param_count; ++$i) {
                $param = $params[$i];
                if (strpos($param, '&') !== false) {
                    $body .= <<<BODY
                    if (\$argc > {$i}) {
                        \$argv[{$i}] = {$param};
                    }
                    
                    BODY;
                }
            }
        } else {
            $params = array_values($method->get_parameters());
            $param_count = count($params);
            for ($i = 0; $i < $param_count; ++$i) {
                $param = $params[$i];
                if (!$param->is_passed_by_reference()) {
                    continue;
                }
                $body .= <<<BODY
                if (\$argc > {$i}) {
                    \$argv[{$i}] =& \${$param->get_name()};
                }
                
                BODY;
            }
        }
        $body .= "\$ret = {$invoke}(__FUNCTION__, \$argv);\n";
        if (!in_array($method->get_return_type(), ['never', 'void'], true)) {
            $body .= "return \$ret;\n";
        }
        return $body . "}\n";
    }
}