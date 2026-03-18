<?php

declare(strict_types=1);

namespace PHP73;

interface MockeryTest_InterfaceWithMethodParamSelf
{
    public function foo(self $bar);
}
