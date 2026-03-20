<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Generator;

use InvalidArgumentException;
class Mock_Definition
{
    /**
     * @var string
     */
    protected $code;
    /**
     * @var MockConfiguration
     */
    protected $config;
    /**
     * @param  string                   $code
     * @throws InvalidArgumentException
     */
    public function __construct(Mock_Configuration $config, $code)
    {
        if (!$config->get_name()) {
            throw new InvalidArgumentException('MockConfiguration must contain a name');
        }
        $this->config = $config;
        $this->code = $code;
    }
    /**
     * @return string
     */
    public function get_class_name()
    {
        return $this->config->get_name();
    }
    /**
     * @return string
     */
    public function get_code()
    {
        return $this->code;
    }
    /**
     * @return MockConfiguration
     */
    public function get_config()
    {
        return $this->config;
    }
}