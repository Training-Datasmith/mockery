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

use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function get_debug_type;
use function implode;
use function in_array;
use InvalidArgumentException;
use function method_exists;
use const PHP_VERSION_ID;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Reflection_Type;
use ReflectionUnionType;
use function sprintf;
use function strpos;
/**
 * @internal
 */
class Reflector
{
    /**
     * List of built-in types.
     *
     * @var list<string>
     */
    public const BUILTIN_TYPES = ['array', 'bool', 'int', 'float', 'null', 'object', 'string'];
    /**
     * List of reserved words.
     *
     * @var list<string>
     */
    public const RESERVED_WORDS = ['bool', 'true', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null', 'object', 'string', 'void'];
    /**
     * Iterable.
     *
     * @var list<string>
     */
    private const ITERABLE = ['iterable'];
    /**
     * Traversable array.
     *
     * @var list<string>
     */
    private const TRAVERSABLE_ARRAY = ['\Traversable', 'array'];
    /**
     * Compute the string representation for the return type.
     *
     * @param bool $withoutNullable
     */
    public static function get_return_type(ReflectionMethod $method, $without_nullable = false): ?string
    {
        $type = $method->get_return_type();
        if (!$type instanceof Reflection_Type && method_exists($method, 'getTentativeReturnType')) {
            $type = $method->get_tentative_return_type();
        }
        if (!$type instanceof Reflection_Type) {
            return null;
        }
        $type_hint = self::get_type_from_reflection_type($type, $method->get_declaring_class());
        return !$without_nullable && $type->allows_null() ? self::format_nullable_type($type_hint) : $type_hint;
    }
    /**
     * Compute the string representation for the simplest return type.
     *
     * @return null|string
     */
    public static function get_simplest_return_type(ReflectionMethod $method)
    {
        $type = $method->get_return_type();
        if (!$type instanceof Reflection_Type && method_exists($method, 'getTentativeReturnType')) {
            $type = $method->get_tentative_return_type();
        }
        if (!$type instanceof Reflection_Type || $type->allows_null()) {
            return null;
        }
        $type_information = self::get_type_information($type, $method->get_declaring_class());
        // return the first primitive type hint
        foreach ($type_information as $info) {
            if ($info['isPrimitive']) {
                return $info['typeHint'];
            }
        }
        // if no primitive type, return the first type
        foreach ($type_information as $info) {
            return $info['typeHint'];
        }
        return null;
    }
    /**
     * Compute the string representation for the paramater type.
     *
     * @param bool $withoutNullable
     */
    public static function get_type_hint(ReflectionParameter $param, $without_nullable = false): ?string
    {
        if (!$param->has_type()) {
            return null;
        }
        $type = $param->get_type();
        $declaring_class = $param->get_declaring_class();
        $type_hint = self::get_type_from_reflection_type($type, $declaring_class);
        return !$without_nullable && $type->allows_null() ? self::format_nullable_type($type_hint) : $type_hint;
    }
    /**
     * Determine if the parameter is typed as an array.
     */
    public static function is_array(ReflectionParameter $param): bool
    {
        $type = $param->get_type();
        return $type instanceof ReflectionNamedType && $type->get_name();
    }
    /**
     * Determine if the given type is a reserved word.
     */
    public static function is_reserved_word(string $type): bool
    {
        return in_array(strtolower($type), self::RESERVED_WORDS, true);
    }
    /**
     * Format the given type as a nullable type.
     */
    private static function format_nullable_type(string $type_hint): string
    {
        if ($type_hint === 'mixed') {
            return $type_hint;
        }
        if (strpos($type_hint, 'null') !== false) {
            return $type_hint;
        }
        if (PHP_VERSION_ID < 80000) {
            return sprintf('?%s', $type_hint);
        }
        return sprintf('%s|null', $type_hint);
    }
    private static function get_type_from_reflection_type(Reflection_Type $type, ReflectionClass $declaring_class): string
    {
        if ($type instanceof ReflectionNamedType) {
            $type_hint = $type->get_name();
            if ($type->is_builtin()) {
                return $type_hint;
            }
            if ($type_hint === 'static') {
                return $type_hint;
            }
            // 'self' needs to be resolved to the name of the declaring class
            if ($type_hint === 'self') {
                $type_hint = $declaring_class->get_name();
            }
            // 'parent' needs to be resolved to the name of the parent class
            if ($type_hint === 'parent') {
                $type_hint = $declaring_class->get_parent_class()->get_name();
            }
            // class names need prefixing with a slash
            return sprintf('\%s', $type_hint);
        }
        if ($type instanceof ReflectionIntersectionType) {
            $types = array_map(static function (Reflection_Type $type) use ($declaring_class): string {
                return self::get_type_from_reflection_type($type, $declaring_class);
            }, $type->get_types());
            return implode('&', $types);
        }
        if ($type instanceof ReflectionUnionType) {
            $types = array_map(static function (Reflection_Type $type) use ($declaring_class): string {
                return self::get_type_from_reflection_type($type, $declaring_class);
            }, $type->get_types());
            $intersect = array_intersect(self::TRAVERSABLE_ARRAY, $types);
            if ($intersect === self::TRAVERSABLE_ARRAY) {
                $types = array_merge(self::ITERABLE, array_diff($types, self::TRAVERSABLE_ARRAY));
            }
            return implode('|', array_map(static function (string $type): string {
                return strpos($type, '&') === false ? $type : sprintf('(%s)', $type);
            }, $types));
        }
        throw new InvalidArgumentException('Unknown ReflectionType: ' . get_debug_type($type));
    }
    /**
     * Get the string representation of the given type.
     *
     * @return list<array{typeHint:string,isPrimitive:bool}>
     */
    private static function get_type_information(Reflection_Type $type, ReflectionClass $declaring_class): array
    {
        // PHP 8 union types and PHP 8.1 intersection types can be recursively processed
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = [];
            foreach ($type->get_types() as $innter_type) {
                foreach (self::get_type_information($innter_type, $declaring_class) as $info) {
                    if ($info['typeHint'] === 'null' && $info['isPrimitive']) {
                        continue;
                    }
                    $types[] = $info;
                }
            }
            return $types;
        }
        // $type must be an instance of \ReflectionNamedType
        $type_hint = $type->get_name();
        // builtins can be returned as is
        if ($type->is_builtin()) {
            return [['typeHint' => $type_hint, 'isPrimitive' => in_array($type_hint, self::BUILTIN_TYPES, true)]];
        }
        // 'static' can be returned as is
        if ($type_hint === 'static') {
            return [['typeHint' => $type_hint, 'isPrimitive' => false]];
        }
        // 'self' needs to be resolved to the name of the declaring class
        if ($type_hint === 'self') {
            $type_hint = $declaring_class->get_name();
        }
        // 'parent' needs to be resolved to the name of the parent class
        if ($type_hint === 'parent') {
            $type_hint = $declaring_class->get_parent_class()->get_name();
        }
        // class names need prefixing with a slash
        return [['typeHint' => sprintf('\%s', $type_hint), 'isPrimitive' => false]];
    }
}