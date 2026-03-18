<?php

declare(strict_types=1);

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery;

class ReceivedMethodCalls
{
    private $methodCalls = [];

    public function push(MethodCall $methodCall): void
    {
        $this->methodCalls[] = $methodCall;
    }

    public function verify(Expectation $expectation): void
    {
        foreach ($this->methodCalls as $methodCall) {
            if ($methodCall->getMethod() !== $expectation->getName()) {
                continue;
            }

            if (! $expectation->matchArgs($methodCall->getArgs())) {
                continue;
            }

            $expectation->verifyCall($methodCall->getArgs());
        }

        $expectation->verify();
    }
}
