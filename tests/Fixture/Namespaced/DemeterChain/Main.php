<?php

declare(strict_types=1);

namespace DemeterChain;

class Main
{
    public function callDemeter(A $a)
    {
        return $a->foo()->bar()->baz();
    }
}
