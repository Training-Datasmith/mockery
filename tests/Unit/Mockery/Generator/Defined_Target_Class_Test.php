<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery\Generator;

use ArrayObject;
use Mockery\Generator\Defined_Target_Class;
use PHP73\MockeryTest_ClassThatExtendsArrayObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @coversDefaultClass \Mockery
 */
final class Defined_Target_Class_Test extends TestCase
{
    public function testItKnowsIfOneOfItsAncestorsIsInternal(): void
    {
        $target = new Defined_Target_Class(new ReflectionClass(ArrayObject::class));
        self::assertTrue($target->has_internal_ancestor());

        $target = new Defined_Target_Class(new ReflectionClass(MockeryTest_ClassThatExtendsArrayObject::class));
        self::assertTrue($target->has_internal_ancestor());

        $target = new Defined_Target_Class(new ReflectionClass(self::class));
        self::assertFalse($target->has_internal_ancestor());
    }
}
