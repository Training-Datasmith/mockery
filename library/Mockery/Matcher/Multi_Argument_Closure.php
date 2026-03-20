<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Matcher;

class Multi_Argument_Closure extends Matcher_Abstract implements Argument_List_Matcher
{
    /**
     * Return a string representation of this Matcher
     */
    public function __toString(): string
    {
        return '<MultiArgumentClosure===true>';
    }
    /**
     * Check if the actual value matches the expected.
     * Actual passed by reference to preserve reference trail (where applicable)
     * back to the original method parameter.
     *
     * @template TMixed
     *
     * @param TMixed $actual
     */
    public function match(&$actual): bool
    {
        return ($this->_expected)(...$actual) === true;
    }
}