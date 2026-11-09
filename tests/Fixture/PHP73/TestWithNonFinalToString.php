<?php

declare(strict_types=1);

namespace PHP73;

class TestWithNonFinalToString
{
    public function __toString()
    {
        return 'bar';
    }
}
