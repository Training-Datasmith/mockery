<?php

declare (strict_types=1);
namespace Mockery\Count_Validator;

interface Count_Validator_Interface
{
    /**
     * Checks if the validator can accept an additional nth call
     *
     * @param int $n
     *
     * @return bool
     */
    public function is_eligible($n);
    /**
     * Validate the call count against this validator
     *
     * @param int $n
     *
     * @return bool
     */
    public function validate($n);
}