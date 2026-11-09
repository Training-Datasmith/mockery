<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Adapter\Phpunit;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\BadMethodCallException;
use PHP73\BaseClassStub;
use Throwable;

/**
 * @coversDefaultClass \Mockery
 */
final class MockeryPHPUnitIntegrationTest extends Mockery_Test_Case
{
    public function testItMarksAPassingTestAsRiskyIfWeThrewExceptions(): void
    {
        $mock = \mock();

        try {
            $mock->foobar();
        } catch (Throwable $e) {
            // exception swallowed...
        }

        $test = \spy(BaseClassStub::class)->makePartial();
        $test->finish();

        $test->shouldHaveReceived()
            ->markAsRisky();
    }

    public function testTheUserCanManuallyDismissAnExceptionToAvoidTheRiskyTest(): void
    {
        $mock = \mock();

        try {
            $mock->foobar();
        } catch (BadMethodCallException $e) {
            $e->dismiss();
        }

        $test = \spy(BaseClassStub::class)->makePartial();
        $test->finish();

        $test->shouldNotHaveReceived()
            ->markAsRisky();
    }
}
