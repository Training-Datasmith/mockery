<?php

declare(strict_types=1);

namespace PHP73;

interface MockeryTest_InterfaceThatExtendsIteratorAggregate extends \IteratorAggregate
{
    public function foo();
}
