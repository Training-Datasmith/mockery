<?php

declare(strict_types=1);

namespace PHP73;

class MockeryTest_ReturnByRef
{
    public $i = 0;

    public function &get()
    {
        return $this->$i;
    }
}
