<?php

declare(strict_types=1);

namespace PHP73;

class NullableObject
{
    public function __invoke(?object $arg): void
    {
    }
}
