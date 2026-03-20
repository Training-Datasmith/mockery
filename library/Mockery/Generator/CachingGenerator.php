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

class Caching_Generator implements Generator
{
    /**
     * @var array<string,string>
     */
    protected $cache = [];
    /**
     * @var Generator
     */
    protected $generator;
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }
    /**
     * @return string
     */
    public function generate(Mock_Configuration $config)
    {
        $hash = $config->get_hash();
        if (array_key_exists($hash, $this->cache)) {
            return $this->cache[$hash];
        }
        return $this->cache[$hash] = $this->generator->generate($config);
    }
}