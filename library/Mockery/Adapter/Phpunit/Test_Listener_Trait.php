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

use function dirname;
use LogicException;
use function method_exists;
use Mockery;
use Php_Unit\Framework\Expectation_Failed_Exception;
use Php_Unit\Framework\Test;
use Php_Unit\Framework\Test_Case;
use Php_Unit\Runner\Base_Test_Runner;
use Php_Unit\Util\Blacklist;
use ReflectionClass;
use function sprintf;
class Test_Listener_Trait
{
    /**
     * endTest is called after each test and checks if \Mockery::close() has
     * been called, and will let the test fail if it hasn't.
     *
     * @param float $time
     */
    public function end_test(Test $test, $time): void
    {
        if (!$test instanceof Test_Case) {
            // We need the getTestResultObject and getStatus methods which are
            // not part of the interface.
            return;
        }
        if ($test->get_status() !== Base_Test_Runner::STATUS_PASSED) {
            // If the test didn't pass there is no guarantee that
            // verifyMockObjects and assertPostConditions have been called.
            // And even if it did, the point here is to prevent false
            // negatives, not to make failing tests fail for more reasons.
            return;
        }
        try {
            // The self() call is used as a sentinel. Anything that throws if
            // the container is closed already will do.
            Mockery::self();
        } catch (LogicException $logic_exception) {
            return;
        }
        $e = new Expectation_Failed_Exception(sprintf("Mockery's expectations have not been verified. Make sure that \\Mockery::close() is called at the end of the test. Consider using %s\\MockeryPHPUnitIntegration or extending %s\\MockeryTestCase.", __NAMESPACE__, __NAMESPACE__));
        /** @var \PHPUnit\Framework\TestResult $result */
        $result = $test->get_test_result_object();
        if ($result !== null) {
            $result->add_failure($test, $e, $time);
        }
    }
    public function start_test_suite(): void
    {
        if (method_exists(Blacklist::class, 'addDirectory')) {
            (new Blacklist())->get_blacklisted_directories();
            Blacklist::add_directory(dirname((new ReflectionClass(Mockery::class))->get_file_name()));
        } else {
            Blacklist::$blacklisted_class_names[Mockery::class] = 1;
        }
    }
}