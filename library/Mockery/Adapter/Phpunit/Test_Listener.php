<?php

declare(strict_types=1);

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery\Adapter\Phpunit;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as PHPUnit_Test_Listener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

class Test_Listener implements PHPUnit_Test_Listener
{
    use TestListenerDefaultImplementation;

    /** @var Test_Listener_Trait */
    private $trait;

    public function __construct()
    {
        $this->trait = new Test_Listener_Trait();
    }

    public function endTest(Test $test, float $time): void
    {
        $this->trait->endTest($test, $time);
    }

    public function startTestSuite(TestSuite $suite): void
    {
        $this->trait->startTestSuite();
    }
}
