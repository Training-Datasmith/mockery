<?php

declare(strict_types=1);

namespace PHP73;

class ParentClass
{
    public function __invoke(self $arg): void
    {
    }
}
