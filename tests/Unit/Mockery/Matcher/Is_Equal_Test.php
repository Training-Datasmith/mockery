<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Matcher;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;

/**
 * @coversDefaultClass \Mockery
 */
final class Is_Equal_Test extends Mockery_Test_Case
{
    use MatcherDataProviderTrait;

    /**
     * @dataProvider isEqualDataProvider
     */
    public function testItWorks($expected, $actual): void
    {
        self::assertTrue(Mockery::is_equal($expected)->match($actual));
    }
}
