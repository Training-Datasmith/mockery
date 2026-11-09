<?php

declare(strict_types=1);

namespace PHP81;

class UsesEnums
{
    public Simple_Enum $enum = Simple_Enum::first;

    public function set(Simple_Enum $enum = Simple_Enum::second)
    {
        $this->enum = $enum;
    }
}
