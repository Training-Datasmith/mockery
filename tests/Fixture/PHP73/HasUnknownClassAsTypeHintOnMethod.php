<?php

declare(strict_types=1);

namespace PHP73;

class HasUnknownClassAsTypeHintOnMethod
{
    public function foo(\UnknownTestClass\Bar $bar)
    {
    }
}
