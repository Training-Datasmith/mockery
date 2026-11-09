<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Matcher;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;

/**
 * @coversDefaultClass \Mockery
 */
final class Is_Same_Test extends Mockery_Test_Case
{
    use MatcherDataProviderTrait;

    /**
     * @dataProvider isSameDataProvider
     */
    public function testItWorks($expected, $actual): void
    {
        self::assertTrue(Mockery::is_same($expected)->match($actual));
    }
}
