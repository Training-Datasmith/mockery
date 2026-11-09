<?php

declare(strict_types=1);

namespace PHP81;

class ArgumentIntersectionTypeHint
{
    public function foo(IntersectionTypeHelper1Interface&IntersectionTypeHelper2Interface $foo)
    {
    }
}
