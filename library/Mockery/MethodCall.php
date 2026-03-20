<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery;

class Method_Call
{
    /**
     * @var array
     */
    private $args;
    /**
     * @var string
     */
    private $method;
    /**
     * @param string $method
     * @param array  $args
     */
    public function __construct($method, $args)
    {
        $this->method = $method;
        $this->args = $args;
    }
    /**
     * @return array
     */
    public function get_args()
    {
        return $this->args;
    }
    /**
     * @return string
     */
    public function get_method()
    {
        return $this->method;
    }
}