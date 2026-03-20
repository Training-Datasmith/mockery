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

use function class_exists;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function strlen;
use UnexpectedValueException;
use function unserialize;
/**
 * This is a trimmed down version of https://github.com/doctrine/instantiator, without the caching mechanism.
 */
final class Instantiator
{
    /**
     * @template TClass of object
     *
     * @param class-string<TClass> $className
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     *
     * @return TClass
     */
    public function instantiate(string $class_name): object
    {
        return $this->build_factory($class_name)();
    }
    /**
     * @throws UnexpectedValueException
     */
    private function attempt_instantiation_via_un_serialization(ReflectionClass $reflection_class, string $serialized_string): void
    {
        set_error_handler(static function ($code, $message, $file, $line) use ($reflection_class, &$error): void {
            $msg = sprintf('Could not produce an instance of "%s" via un-serialization, since an error was triggered in file "%s" at line "%d"', $reflection_class->get_name(), $file, $line);
            $error = new UnexpectedValueException($msg, 0, new Exception($message, $code));
        });
        try {
            unserialize($serialized_string);
        } catch (Exception $exception) {
            restore_error_handler();
            throw new UnexpectedValueException(sprintf('An exception was raised while trying to instantiate an instance of "%s" via un-serialization', $reflection_class->get_name()), 0, $exception);
        }
        restore_error_handler();
        if ($error instanceof UnexpectedValueException) {
            throw $error;
        }
    }
    /**
     * Builds a {@see Closure} capable of instantiating the given $className without invoking its constructor.
     */
    private function build_factory(string $class_name): Closure
    {
        $reflection_class = $this->get_reflection_class($class_name);
        if ($this->is_instantiable_via_reflection($reflection_class)) {
            return static function () use ($reflection_class): object {
                return $reflection_class->new_instance_without_constructor();
            };
        }
        $serialized_string = sprintf('O:%d:"%s":0:{}', strlen($class_name), $class_name);
        $this->attempt_instantiation_via_un_serialization($reflection_class, $serialized_string);
        return static function () use ($serialized_string) {
            return unserialize($serialized_string);
        };
    }
    /**
     * @throws InvalidArgumentException
     */
    private function get_reflection_class(string $class_name): ReflectionClass
    {
        if (!class_exists($class_name)) {
            throw new InvalidArgumentException(sprintf('Class:%s does not exist', $class_name));
        }
        $reflection = new ReflectionClass($class_name);
        if ($reflection->is_abstract()) {
            throw new InvalidArgumentException(sprintf('Class:%s is an abstract class', $class_name));
        }
        return $reflection;
    }
    /**
     * Verifies if the class is instantiable via reflection
     */
    private function is_instantiable_via_reflection(ReflectionClass $reflection_class): bool
    {
        return !($reflection_class->is_internal() && $reflection_class->is_final());
    }
}