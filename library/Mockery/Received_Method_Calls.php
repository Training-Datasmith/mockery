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

class Received_Method_Calls
{
    private $method_calls = [];
    public function push(Method_Call $method_call): void
    {
        $this->method_calls[] = $method_call;
    }
    public function verify(Expectation $expectation): void
    {
        foreach ($this->method_calls as $method_call) {
            if ($method_call->get_method() !== $expectation->get_name()) {
                continue;
            }
            if (!$expectation->match_args($method_call->get_args())) {
                continue;
            }
            $expectation->verify_call($method_call->get_args());
        }
        $expectation->verify();
    }
}