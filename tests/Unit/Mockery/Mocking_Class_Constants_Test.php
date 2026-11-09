<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP73\ClassWithConstants;

/**
 * @coversDefaultClass \Mockery
 */
final class Mocking_Class_Constants_Test extends Mockery_Test_Case
{
    public function testItShouldAllowToMockClassConstants(): void
    {
        Mockery::get_configuration()->set_constants_map([
            ClassWithConstants::class => [
                'FOO' => 'baz',
                'X' => 2,
                'BAZ' => [
                    'qux' => 'daz',
                ],
            ],
        ]);

        $mock = Mockery::mock('overload:' . ClassWithConstants::class);

        self::assertSame('baz', $mock::FOO);
        self::assertSame(2, $mock::X);
        self::assertSame([
            'qux' => 'daz',
        ], $mock::BAZ);
    }
}
