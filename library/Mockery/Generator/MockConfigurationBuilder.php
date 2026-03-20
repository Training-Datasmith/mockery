<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Generator;

use function array_diff;
class Mock_Configuration_Builder
{
    /**
     * @var list<string>
     */
    protected $black_listed_methods = [
        '__call',
        '__callStatic',
        '__clone',
        '__wakeup',
        '__set',
        '__get',
        '__toString',
        '__isset',
        '__destruct',
        '__debugInfo',
        ## mocking this makes it difficult to debug with xdebug
        // below are reserved words in PHP
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
    ];
    /**
     * @var array
     */
    protected $constants_map = [];
    /**
     * @var bool
     */
    protected $instance_mock = false;
    /**
     * @var bool
     */
    protected $mock_original_destructor = false;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var array
     */
    protected $parameter_overrides = [];
    /**
     * @var list<string>
     */
    protected $php7semi_reserved_keywords = ['callable', 'class', 'trait', 'extends', 'implements', 'static', 'abstract', 'final', 'public', 'protected', 'private', 'const', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endwhile', 'and', 'global', 'goto', 'instanceof', 'insteadof', 'interface', 'namespace', 'new', 'or', 'xor', 'try', 'use', 'var', 'exit', 'list', 'clone', 'include', 'include_once', 'throw', 'array', 'print', 'echo', 'require', 'require_once', 'return', 'else', 'elseif', 'default', 'break', 'continue', 'switch', 'yield', 'function', 'if', 'endswitch', 'finally', 'for', 'foreach', 'declare', 'case', 'do', 'while', 'as', 'catch', 'die', 'self', 'parent'];
    /**
     * @var array
     */
    protected $targets = [];
    /**
     * @var array
     */
    protected $white_listed_methods = [];
    public function __construct()
    {
        $this->black_listed_methods = array_diff($this->black_listed_methods, $this->php7semi_reserved_keywords);
    }
    /**
     * @param  string $blackListedMethod
     */
    public function add_black_listed_method($black_listed_method): self
    {
        $this->black_listed_methods[] = $black_listed_method;
        return $this;
    }
    /**
     * @param  list<string> $blackListedMethods
     */
    public function add_black_listed_methods(array $black_listed_methods): self
    {
        foreach ($black_listed_methods as $method) {
            $this->add_black_listed_method($method);
        }
        return $this;
    }
    /**
     * @param  class-string $target
     */
    public function add_target($target): self
    {
        $this->targets[] = $target;
        return $this;
    }
    /**
     * @param  list<class-string> $targets
     */
    public function add_targets($targets): self
    {
        foreach ($targets as $target) {
            $this->add_target($target);
        }
        return $this;
    }
    public function add_white_listed_method($white_listed_method): self
    {
        $this->white_listed_methods[] = $white_listed_method;
        return $this;
    }
    public function add_white_listed_methods(array $white_listed_methods): self
    {
        foreach ($white_listed_methods as $method) {
            $this->add_white_listed_method($method);
        }
        return $this;
    }
    public function get_mock_configuration(): \Mockery\Generator\Mock_Configuration
    {
        return new Mock_Configuration($this->targets, $this->black_listed_methods, $this->white_listed_methods, $this->name, $this->instance_mock, $this->parameter_overrides, $this->mock_original_destructor, $this->constants_map);
    }
    /**
     * @param  list<string> $blackListedMethods
     */
    public function set_black_listed_methods(array $black_listed_methods): self
    {
        $this->black_listed_methods = $black_listed_methods;
        return $this;
    }
    public function set_constants_map(array $map): self
    {
        $this->constants_map = $map;
        return $this;
    }
    /**
     * @param bool $instanceMock
     */
    public function set_instance_mock($instance_mock): self
    {
        $this->instance_mock = (bool) $instance_mock;
        return $this;
    }
    /**
     * @param bool $mockDestructor
     */
    public function set_mock_original_destructor($mock_destructor): self
    {
        $this->mock_original_destructor = (bool) $mock_destructor;
        return $this;
    }
    /**
     * @param string $name
     */
    public function set_name($name): self
    {
        $this->name = $name;
        return $this;
    }
    public function set_parameter_overrides(array $overrides): self
    {
        $this->parameter_overrides = $overrides;
        return $this;
    }
    /**
     * @param  list<string> $whiteListedMethods
     */
    public function set_white_listed_methods(array $white_listed_methods): self
    {
        $this->white_listed_methods = $white_listed_methods;
        return $this;
    }
}