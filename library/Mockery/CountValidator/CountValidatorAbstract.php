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

use Mockery\Expectation;
abstract class Count_Validator_Abstract implements Count_Validator_Interface
{
    /**
     * Expectation for which this validator is assigned
     *
     * @var Expectation
     */
    protected $_expectation;
    /**
     * Call count limit
     *
     * @var int
     */
    protected $_limit;
    /**
     * Set Expectation object and upper call limit
     *
     * @param int $limit
     */
    public function __construct(Expectation $expectation, $limit)
    {
        $this->_expectation = $expectation;
        $this->_limit = $limit;
    }
    /**
     * Checks if the validator can accept an additional nth call
     *
     * @param int $n
     *
     * @return bool
     */
    public function is_eligible($n)
    {
        return $n < $this->_limit;
    }
    /**
     * Validate the call count against this validator
     *
     * @param int $n
     *
     * @return bool
     */
    abstract public function validate($n);
}