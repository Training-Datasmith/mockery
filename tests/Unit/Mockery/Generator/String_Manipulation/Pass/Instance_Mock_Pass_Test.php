<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator\String_Manipulation\Pass;

use Mockery\Generator\Mock_Configuration_Builder;
use Mockery\Generator\String_Manipulation\Pass\Instance_Mock_Pass;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 */
final class Instance_Mock_Pass_Test extends TestCase
{
    public function testShouldAppendConstructorAndPropertyForInstanceMock(): void
    {
        $builder = new Mock_Configuration_Builder();
        $builder->set_instance_mock(true);
        $config = $builder->get_mock_configuration();
        $pass = new Instance_Mock_Pass();
        $code = $pass->apply('class Dave { }', $config);

        self::assertNotFalse(\mb_strpos($code, 'public function __construct'));

        self::assertNotFalse(\mb_strpos($code, 'protected $_mockery_ignoreVerification'));

        self::assertNotFalse(\mb_strpos($code, '$this->_mockery_constructor_called(func_get_args());'));
    }
}
