<?php

declare(strict_types=1);

namespace PHP73;

class MockeryTest_MethodWithRequiredParamWithDefaultValue
{
    public function foo($baz, ?\DateTime $bar = null)
    {
    }
}
