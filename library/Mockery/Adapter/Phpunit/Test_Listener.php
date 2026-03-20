<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Adapter\Phpunit;

use Php_Unit\Framework\Test;
use Php_Unit\Framework\Test_Listener as PHPUnitTestListener;
use Php_Unit\Framework\Test_Listener_Default_Implementation;
use Php_Unit\Framework\Test_Suite;
class Test_Listener implements Php_Unit_Test_Listener
{
    use Test_Listener_Default_Implementation;
    private $trait;
    public function __construct()
    {
        $this->trait = new Test_Listener_Trait();
    }
    public function end_test(Test $test, float $time): void
    {
        $this->trait->end_test($test, $time);
    }
    public function start_test_suite(Test_Suite $suite): void
    {
        $this->trait->start_test_suite();
    }
}