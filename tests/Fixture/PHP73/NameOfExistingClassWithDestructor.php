<?php

declare(strict_types=1);

namespace PHP73;

class NameOfExistingClassWithDestructor
{
    public static $isDestructorWasCalled = false;

    public function __destruct()
    {
        self::$isDestructorWasCalled = true;
    }
}
