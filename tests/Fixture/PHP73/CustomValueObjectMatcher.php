<?php

declare(strict_types=1);

namespace PHP73;

use Mockery\Matcher\Matcher_Abstract;

class CustomValueObjectMatcher extends Matcher_Abstract
{
    public function match(&$actual)
    {
        return $actual->value === $this->_expected->value;
    }

    public function __toString()
    {
        return '<customMatcher>';
    }
}
