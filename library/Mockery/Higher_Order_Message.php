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

use Closure;
/**
 * @method Expectation withArgs(array|Closure $args)
 */
class Higher_Order_Message
{
    /**
     * @var string
     */
    private $method;
    /**
     * @var LegacyMockInterface|MockInterface
     */
    private $mock;
    public function __construct(Mock_Interface $mock, $method)
    {
        $this->mock = $mock;
        $this->method = $method;
    }
    /**
     * @param array  $args
     * @return Expectation|ExpectationInterface|HigherOrderMessage
     */
    public function __call(string $method, array $args)
    {
        if ($this->method === 'shouldNotHaveReceived') {
            return $this->mock->{$this->method}($method, $args);
        }
        $expectation = $this->mock->{$this->method}($method);
        return $expectation->with_args($args);
    }
}