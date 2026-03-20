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

use function implode;
use function str_replace;
class Mock_Name_Builder
{
    /**
     * @var int
     */
    protected static $mock_counter = 0;
    /**
     * @var list<string>
     */
    protected $parts = [];
    /**
     * @param string $part
     */
    public function add_part($part): self
    {
        $this->parts[] = $part;
        return $this;
    }
    public function build(): string
    {
        $parts = ['Mockery', static::$mock_counter++];
        foreach ($this->parts as $part) {
            $parts[] = str_replace('\\', '_', $part);
        }
        return implode('_', $parts);
    }
}