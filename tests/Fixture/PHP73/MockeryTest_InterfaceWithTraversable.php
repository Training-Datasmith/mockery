<?php

declare(strict_types=1);

namespace PHP73;

interface MockeryTest_InterfaceWithTraversable extends \ArrayAccess, \Traversable, \Countable
{
    public function self();
}
