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

use function file_get_contents;
use Mockery\Generator\String_Manipulation\Pass\Avoid_Method_Clash_Pass;
use Mockery\Generator\String_Manipulation\Pass\Call_Type_Hint_Pass;
use Mockery\Generator\String_Manipulation\Pass\Class_Attributes_Pass;
use Mockery\Generator\String_Manipulation\Pass\Class_Name_Pass;
use Mockery\Generator\String_Manipulation\Pass\Class_Pass;
use Mockery\Generator\String_Manipulation\Pass\Constants_Pass;
use Mockery\Generator\String_Manipulation\Pass\Instance_Mock_Pass;
use Mockery\Generator\String_Manipulation\Pass\Interface_Pass;
use Mockery\Generator\String_Manipulation\Pass\Magic_Method_Type_Hints_Pass;
use Mockery\Generator\String_Manipulation\Pass\Method_Definition_Pass;
use Mockery\Generator\String_Manipulation\Pass\Pass;
use Mockery\Generator\String_Manipulation\Pass\Remove_Builtin_Methods_That_Are_Final_Pass;
use Mockery\Generator\String_Manipulation\Pass\Remove_Destructor_Pass;
use Mockery\Generator\String_Manipulation\Pass\Remove_Unserialize_For_Internal_Serializable_Classes_Pass;
use Mockery\Generator\String_Manipulation\Pass\Trait_Pass;
class String_Manipulation_Generator implements Generator
{
    /**
     * @var list<Pass>
     */
    protected $passes = [];
    /**
     * @var string
     */
    private $code;
    /**
     * @param list<Pass> $passes
     */
    public function __construct(array $passes)
    {
        $this->passes = $passes;
        $this->code = file_get_contents(__DIR__ . '/../Mock.php');
    }
    public function add_pass(Pass $pass): void
    {
        $this->passes[] = $pass;
    }
    public function generate(Mock_Configuration $config): \Mockery\Generator\Mock_Definition
    {
        $class_name = $config->get_name() ?: $config->generate_name();
        $named_config = $config->rename($class_name);
        $code = $this->code;
        foreach ($this->passes as $pass) {
            $code = $pass->apply($code, $named_config);
        }
        return new Mock_Definition($named_config, $code);
    }
    /**
     * Creates a new StringManipulationGenerator with the default passes
     */
    public static function with_default_passes(): self
    {
        return new static([new Call_Type_Hint_Pass(), new Magic_Method_Type_Hints_Pass(), new Class_Pass(), new Trait_Pass(), new Class_Name_Pass(), new Instance_Mock_Pass(), new Interface_Pass(), new Avoid_Method_Clash_Pass(), new Method_Definition_Pass(), new Remove_Unserialize_For_Internal_Serializable_Classes_Pass(), new Remove_Builtin_Methods_That_Are_Final_Pass(), new Remove_Destructor_Pass(), new Constants_Pass(), new Class_Attributes_Pass()]);
    }
}