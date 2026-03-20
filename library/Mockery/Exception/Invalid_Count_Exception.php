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

use function in_array;
use Mockery\Count_Validator\Exception;
use Mockery\Legacy_Mock_Interface;
class Invalid_Count_Exception extends Exception
{
    /**
     * @var int|null
     */
    protected $actual;
    /**
     * @var int
     */
    protected $expected = 0;
    /**
     * @var string
     */
    protected $expected_comparative = '<=';
    /**
     * @var string|null
     */
    protected $method;
    /**
     * @var LegacyMockInterface|null
     */
    protected $mock_object;
    /**
     * @return int|null
     */
    public function get_actual_count()
    {
        return $this->actual;
    }
    /**
     * @return int
     */
    public function get_expected_count()
    {
        return $this->expected;
    }
    /**
     * @return string
     */
    public function get_expected_count_comparative()
    {
        return $this->expected_comparative;
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
     * @throws RuntimeException
     * @return string|null
     */
    public function get_mock_name()
    {
        $mock = $this->get_mock();
        if ($mock === null) {
            return '';
        }
        return $mock->mockery_get_name();
    }
    /**
     * @param  int  $count
     */
    public function set_actual_count($count): self
    {
        $this->actual = $count;
        return $this;
    }
    /**
     * @param  int  $count
     */
    public function set_expected_count($count): self
    {
        $this->expected = $count;
        return $this;
    }
    /**
     * @param  string $comp
     */
    public function set_expected_count_comparative($comp): self
    {
        if (!in_array($comp, ['=', '>', '<', '>=', '<='], true)) {
            throw new RuntimeException('Illegal comparative for expected call counts set: ' . $comp);
        }
        $this->expected_comparative = $comp;
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