<?php

declare(strict_types=1);

namespace PHP83;

trait Traits
{
    public const string BAR = Enums::FOO;

    public function foo(): string
    {
        return Interfaces::BAR;
    }
}
