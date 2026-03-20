<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery;

class Verification_Expectation extends Expectation
{
    public function __clone()
    {
        parent::__clone();
        $this->_actual_count = 0;
    }
    public function clear_count_validators(): void
    {
        $this->_count_validators = [];
    }
}