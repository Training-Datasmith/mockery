<?php

declare(strict_types=1);

namespace PHP73;

abstract class MockeryTest_PartialAbstractClass
{
    abstract public function foo();

    public function bar()
    {
        return 'abc';
    }
}
