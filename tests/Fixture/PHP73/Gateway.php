<?php

declare(strict_types=1);

namespace PHP73;

class Gateway
{
    public function __call($method, $args)
    {
        $m = new SoCool();
        return call_user_func_array([$m, $method], $args);
    }
}
