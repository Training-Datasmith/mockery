<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Generator\String_Manipulation\Pass;

use Mockery\Generator\Mock_Configuration;
use function strrpos;
use function substr;
class Instance_Mock_Pass implements Pass
{
    public const INSTANCE_MOCK_CODE = <<<MOCK
    
        protected \$_mockery_ignoreVerification = true;
    
        public function __construct()
        {
            \$this->_mockery_ignoreVerification = false;
            \$associatedRealObject = \\Mockery::fetchMock(__CLASS__);
    
            foreach (get_object_vars(\$this) as \$attr => \$val) {
                if (\$attr !== "_mockery_ignoreVerification" && \$attr !== "_mockery_expectations") {
                    \$this->\$attr = \$associatedRealObject->\$attr;
                }
            }
    
            \$directors = \$associatedRealObject->mockery_get_expectations();
            foreach (\$directors as \$method=>\$director) {
                \$existingDirector = \$this->mockery_get_expectations_for(\$method);
                if (!\$existingDirector) {
                    \$existingDirector = new \\Mockery\\Expectation_Director(\$method, \$this);
                    \$this->mockery_set_expectations_for(\$method, \$existingDirector);
                }
                \$expectations = \$director->get_expectations();
                foreach (\$expectations as \$expectation) {
                    \$clonedExpectation = clone \$expectation;
                    \$existingDirector->add_expectation(\$clonedExpectation);
                }
                \$defaultExpectations = \$director->get_default_expectations();
                foreach (array_reverse(\$defaultExpectations) as \$expectation) {
                    \$clonedExpectation = clone \$expectation;
                    \$existingDirector->add_expectation(\$clonedExpectation);
                    \$existingDirector->make_expectation_default(\$clonedExpectation);
                }
            }
            \\Mockery::get_container()->remember_mock(\$this);
    
            \$this->_mockery_constructorCalled(func_get_args());
        }
    MOCK;
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        if ($config->is_instance_mock()) {
            return $this->append_to_class($code, static::INSTANCE_MOCK_CODE);
        }
        return $code;
    }
    protected function append_to_class($class, string $code): string
    {
        $last_brace = strrpos($class, '}');
        return substr($class, 0, $last_brace) . $code . "\n    }\n";
    }
}
