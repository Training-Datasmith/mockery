<?php

declare(strict_types=1);

namespace PHP73;

use Mockery\Adapter\Phpunit\Mockery_Php_Unit_Integration;

class BaseClassStub
{
    use Mockery_Php_Unit_Integration;

    public function finish()
    {
        $this->check_mockery_exceptions();
    }

    public function markAsRisky()
    {
    }
}
