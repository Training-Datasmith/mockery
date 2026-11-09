<?php

declare(strict_types=1);

namespace Tests\Unit;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;

abstract class AbstractTestCase extends Mockery_Test_Case
{
    public function assertInvalidMock(string $class, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        \mock($class);
    }

    public function assertValidMock(string $class): void
    {
        self::assertInstanceOf($class, \mock($class));
    }
}
