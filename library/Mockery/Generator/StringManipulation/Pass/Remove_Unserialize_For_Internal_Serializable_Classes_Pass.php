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
use const PHP_VERSION_ID;
use function strrpos;
use function substr;
/**
 * Internal classes can not be instantiated with the newInstanceWithoutArgs
 * reflection method, so need the serialization hack. If the class also
 * implements Serializable, we need to replace the standard unserialize method
 * definition with a dummy
 */
class Remove_Unserialize_For_Internal_Serializable_Classes_Pass implements Pass
{
    public const DUMMY_METHOD_DEFINITION = 'public function unserialize(string $data): void {} ';
    public const DUMMY_METHOD_DEFINITION_LEGACY = 'public function unserialize($string) {} ';
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, Mock_Configuration $config)
    {
        $target = $config->get_target_class();
        if (!$target) {
            return $code;
        }
        if (!$target->has_internal_ancestor() || !$target->implements_interface('Serializable')) {
            return $code;
        }
        return $this->append_to_class($code, PHP_VERSION_ID < 80100 ? self::DUMMY_METHOD_DEFINITION_LEGACY : self::DUMMY_METHOD_DEFINITION);
    }
    protected function append_to_class($class, string $code): string
    {
        $last_brace = strrpos($class, '}');
        return substr($class, 0, $last_brace) . $code . "\n    }\n";
    }
}