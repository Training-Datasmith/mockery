<?php

declare(strict_types=1);

namespace Tests\Unit\PHP74;

use Mockery;
use Mockery\Exception\No_Matching_Expectation_Exception;
use PHP74\DummyClass;
use Tests\Unit\Abstract_Test_Case;

/**
 * @coversDefaultClass \Mockery
 * @requires PHP 7.4
 * @see https://github.com/mockery/mockery/issues/1132
 */
final class Test_Case1132_Test extends Abstract_Test_Case
{
    public function testCase(): void
    {
        $mock = Mockery::mock('TestCase1132');

        $expectedDummy = new DummyClass();
        $expectedDummy->number = 1;

        $mock->allows()->saveDummy($expectedDummy);

        $this->expectException(No_Matching_Expectation_Exception::class);

        $actualDummy = new DummyClass();
        $mock->saveDummy($actualDummy);
    }
}
