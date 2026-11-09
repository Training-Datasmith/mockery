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

use LogicException;
use function method_exists;
use Mockery;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Blacklist;
use ReflectionClass;
use function dirname;
use function sprintf;

class Test_Listener_Trait
{
    /**
     * endTest is called after each test and checks if \Mockery::close() has
     * been called, and will let the test fail if it hasn't.
     *
     * @param float $time
     */
    public function endTest(Test $test, $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        if ($test->getStatus() !== BaseTestRunner::STATUS_PASSED) {
            return;
        }

        try {
            Mockery::self();
        } catch (LogicException $logicException) {
            return;
        }

        $e = new ExpectationFailedException(sprintf(
            "Mockery's expectations have not been verified. Make sure that \\Mockery::close() is called at the end of the test. Consider using %s\\Mockery_Php_Unit_Integration or extending %s\\Mockery_Test_Case.",
            __NAMESPACE__,
            __NAMESPACE__
        ));

        $result = $test->getTestResultObject();
        if ($result !== null) {
            $result->addFailure($test, $e, $time);
        }
    }

    public function startTestSuite(): void
    {
        if (method_exists(Blacklist::class, 'addDirectory')) {
            (new Blacklist())->getBlacklistedDirectories();
            Blacklist::addDirectory(dirname((new ReflectionClass(Mockery::class))->getFileName()));
        } else {
            Blacklist::$blacklistedClassNames[Mockery::class] = 1;
        }
    }
}
