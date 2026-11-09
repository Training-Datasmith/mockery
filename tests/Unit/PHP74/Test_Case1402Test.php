<?php

declare(strict_types=1);

namespace Tests\Unit\PHP74;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use PHP74\Regression\Issue1402\Service;

/**
 * @coversDefaultClass \Mockery
 * @requires PHP 7.4
 * @see https://github.com/mockery/mockery/issues/1402
 */
final class TestCase1402Test extends Mockery_Test_Case
{
    public function testMethod(): void
    {
        $banana = Mockery::mock(Service::class, [1])->make_partial();

        $banana->allows('test')
            ->and_returns(2);

        self::assertSame(2, $banana->test());
    }
}
