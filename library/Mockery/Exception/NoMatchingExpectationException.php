<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Exception;

use Mockery\Exception;
use Mockery\Legacy_Mock_Interface;
class No_Matching_Expectation_Exception extends Exception
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
    protected $mock_object;
    /**
     * @return array<mixed>
     */
    public function get_actual_arguments()
    {
        return $this->actual;
    }
    /**
     * @return string|null
     */
    public function get_method_name()
    {
        return $this->method;
    }
    /**
     * @return LegacyMockInterface|null
     */
    public function get_mock()
    {
        return $this->mock_object;
    }
    /**
     * @return string|null
     */
    public function get_mock_name()
    {
        $mock = $this->get_mock();
        if ($mock === null) {
            return $mock;
        }
        return $mock->mockery_get_name();
    }
    /**
     * @todo Rename param `count` to `args`
     * @template TMixed
     *
     * @param  array<TMixed> $count
     */
    public function set_actual_arguments($count): self
    {
        $this->actual = $count;
        return $this;
    }
    /**
     * @param  string $name
     */
    public function set_method_name($name): self
    {
        $this->method = $name;
        return $this;
    }
    public function set_mock(Legacy_Mock_Interface $mock): self
    {
        $this->mock_object = $mock;
        return $this;
    }
}