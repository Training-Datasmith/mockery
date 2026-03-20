<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Count_Validator;

use Mockery\Exception\Invalid_Count_Exception;
use const PHP_EOL;
class At_Most extends Count_Validator_Abstract
{
    /**
     * Validate the call count against this validator
     *
     * @param int $n
     *
     * @throws InvalidCountException
     */
    public function validate($n): void
    {
        if ($this->_limit < $n) {
            $exception = new Invalid_Count_Exception('Method ' . $this->_expectation . ' from ' . $this->_expectation->get_mock()->mockery_get_name() . ' should be called' . PHP_EOL . ' at most ' . $this->_limit . ' times but called ' . $n . ' times.');
            $exception->set_mock($this->_expectation->get_mock())->set_method_name((string) $this->_expectation)->set_expected_count_comparative('<=')->set_expected_count($this->_limit)->set_actual_count($n);
            throw $exception;
        }
    }
}