<?php

declare(strict_types=1);

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery\Matcher;

use function preg_match;

class Pattern extends MatcherAbstract
{
    /**
     * Return a string representation of this Matcher
     */
    public function __toString(): string
    {
        return '<Pattern>';
    }

    /**
     * Check if the actual value matches the expected pattern.
     *
     * @template TMixed
     *
     * @param TMixed $actual
     */
    public function match(&$actual): bool
    {
        return preg_match($this->_expected, (string) $actual) >= 1;
    }
}
