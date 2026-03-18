<?php

declare(strict_types=1);

namespace DemeterChain;

class B
{
    public function bar(): C
    {
        return new C();
    }

    public function qux(): C
    {
        return new C();
    }
}
