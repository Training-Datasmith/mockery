<?php

declare(strict_types=1);

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery\Exception;

use Mockery\Exception;
use Mockery\LegacyMockInterface;

class NoMatchingExpectationException extends Exception
{
    /**
     * @var array<mixed>
     */
    protected $actual = [];

    /**
     * @var string|null
     */
    protected $method;

    /**
     * @var LegacyMockInterface|null
     */
    protected $mockObject;

    /**
     * @return array<mixed>
     */
    public function getActualArguments()
    {
        return $this->actual;
    }

    /**
     * @return string|null
     */
    public function getMethodName()
    {
        return $this->method;
    }

    /**
     * @return LegacyMockInterface|null
     */
    public function getMock()
    {
        return $this->mockObject;
    }

    /**
     * @return string|null
     */
    public function getMockName()
    {
        $mock = $this->getMock();

        if ($mock === null) {
            return $mock;
        }

        return $mock->mockery_getName();
    }

    /**
     * @todo Rename param `count` to `args`
     * @template TMixed
     *
     * @param  array<TMixed> $count
     */
    public function setActualArguments($count): self
    {
        $this->actual = $count;
        return $this;
    }

    /**
     * @param  string $name
     */
    public function setMethodName($name): self
    {
        $this->method = $name;
        return $this;
    }

    public function setMock(LegacyMockInterface $mock): self
    {
        $this->mockObject = $mock;
        return $this;
    }
}
