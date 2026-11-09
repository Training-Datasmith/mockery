<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Adapter\Phpunit;

use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Exception\Bad_Method_Call_Exception;
use PHP73\BaseClassStub;
use Throwable;

/**
 * @coversDefaultClass \Mockery
 */
final class Mockery_PHP_Unit_Integration_Test extends Mockery_Test_Case
{
    public function testItMarksAPassingTestAsRiskyIfWeThrewExceptions(): void
    {
        $mock = \mock();

        try {
            $mock->foobar();
        } catch (Throwable $e) {
            // exception swallowed...
        }

        $test = \spy(BaseClassStub::class)->make_partial();
        $test->finish();

        $test->should_have_received()
            ->markAsRisky();
    }

    public function testTheUserCanManuallyDismissAnExceptionToAvoidTheRiskyTest(): void
    {
        $mock = \mock();

        try {
            $mock->foobar();
        } catch (Bad_Method_Call_Exception $e) {
            $e->dismiss();
        }

        $test = \spy(BaseClassStub::class)->make_partial();
        $test->finish();

        $test->should_not_have_received()
            ->markAsRisky();
    }
}
