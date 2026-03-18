<?php

declare(strict_types=1);

namespace PHP73;

class ChildClass extends ParentClass
{
    public function __invoke(parent $arg): void
    {
    }
}
