<?php

declare(strict_types=1);

namespace PHP73;

class MockeryTest_CallStatic
{
    public static function __callStatic($method, $args)
    {
    }
}
