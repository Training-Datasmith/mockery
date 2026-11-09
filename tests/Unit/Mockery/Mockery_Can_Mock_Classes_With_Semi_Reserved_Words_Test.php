<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use PHP73\SemiReservedWordsAsMethods;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Mockery_Can_Mock_Classes_With_Semi_Reserved_Words_Test extends TestCase
{
    public function testMockSemiReservedWordsAsMethods(): void
    {
        $mock = Mockery::mock(SemiReservedWordsAsMethods::class);

        $mock->should_receive('include')
            ->and_return('foo');

        self::assertTrue(\method_exists($mock, 'include'));
        self::assertSame('foo', $mock->include());
    }
}
