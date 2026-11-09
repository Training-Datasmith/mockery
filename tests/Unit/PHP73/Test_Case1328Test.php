<?php

declare(strict_types=1);

namespace Tests\Unit\PHP73;

use DateTime;
use InvalidArgumentException;
use Mockery;
use Mockery\Exception\Invalid_Count_Exception;
use Mockery\Expectation;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 * @requires PHP 7.3
 * @see https://github.com/mockery/mockery/issues/1328
 */
final class TestCase1328Test extends TestCase
{
    public function testShouldFailWithAnInvocationCountError(): void
    {
        $this->expectException(Invalid_Count_Exception::class);

        $mock = Mockery::mock(DateTime::class);

        $mock->should_not_receive('format');

        $mock->format('Y');

        Mockery::close();
    }

    public function testThrowsInvalidArgumentExceptionForChainingAdditionalInvocationCountMethod(): void
    {
        \set_error_handler(
            static function (int $errorCode, string $errorMessage): void {
                \restore_error_handler();
                throw new InvalidArgumentException($errorMessage, $errorCode);
            },
            E_ALL
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Expectation::ERROR_ZERO_INVOCATION);

        $mock = Mockery::mock(DateTime::class);

        $mock->should_not_receive('format')
            ->times(0);

        $mock->format('Y');

        Mockery::close();
    }

    public function testThrowsInvalidArgumentExceptionWhenInvocationCountChanges(): void
    {
        \set_error_handler(
            static function (int $errorCode, string $errorMessage): void {
                \restore_error_handler();
                throw new InvalidArgumentException($errorMessage, $errorCode);
            },
            E_ALL
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Expectation::ERROR_ZERO_INVOCATION);

        $mock = Mockery::mock(DateTime::class);

        $mock->should_not_receive('format')
            ->once();

        $mock->format('Y');

        Mockery::close();
    }
}
