<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use ArrayAccess;
use ArrayObject;
use Countable;
use DateTime;
use Exception;
use Foo\Bar;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use LogicException;

use function mock;

use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Container;
use Mockery\Exception\Bad_Method_Call_Exception;
use Mockery\Exception\No_Matching_Expectation_Exception;
use Mockery\Exception\Runtime_Exception;
use Mockery\Generator\Mock_Configuration_Builder;
use Mockery\Mock_Interface;
use MongoCollection;
use MyNamespace\MyClass;
use MyNamespace\MyClass10;
use MyNamespace\MyClass11;
use MyNamespace\MyClass12;
use MyNamespace\MyClass13;
use MyNamespace\MyClass14;
use MyNamespace\MyClass15;
use MyNamespace\MyClass16;
use MyNameSpace\MyClass2;
use MyNamespace\MyClass4;
use MyNamespace\MyClass5;
use MyNamespace\MyClass6;
use MyNamespace\MyClass8;
use MyNamespace\MyClass9;
use MyNameSpace\StaticNoMethod;
use PHP73\EmptyConstructorTest;
use PHP73\Gateway;
use PHP73\MockeryFoo3;
use PHP73\MockeryFoo4;
use PHP73\MockeryTest_AbstractWithAbstractMethod;
use PHP73\MockeryTest_AbstractWithAbstractPublicMethod;
use PHP73\MockeryTest_Call1;
use PHP73\MockeryTest_Call2;
use PHP73\MockeryTest_CallStatic;
use PHP73\MockeryTest_ClassConstructor;
use PHP73\MockeryTest_ClassConstructor2;
use PHP73\MockeryTest_ClassThatDescendsFromInternalClass;
use PHP73\MockeryTest_ExistingProperty;
use PHP73\MockeryTest_ImplementsIterator;
use PHP73\MockeryTest_ImplementsIteratorAggregate;
use PHP73\MockeryTest_Interface;
use PHP73\MockeryTest_Interface1;
use PHP73\MockeryTest_Interface2;
use PHP73\MockeryTest_InterfaceThatExtendsIterator;
use PHP73\MockeryTest_InterfaceThatExtendsIteratorAggregate;
use PHP73\MockeryTest_InterfaceWithAbstractMethod;
use PHP73\MockeryTest_InterfaceWithMethodParamSelf;
use PHP73\MockeryTest_InterfaceWithPublicStaticMethod;
use PHP73\MockeryTest_InterfaceWithTraversable;
use PHP73\MockeryTest_IssetMethod;
use PHP73\MockeryTest_Lowercase_ToString;
use PHP73\MockeryTest_MethodParamRef;
use PHP73\MockeryTest_MethodParamRef2;
use PHP73\MockeryTest_MethodWithRequiredParamWithDefaultValue;
use PHP73\MockeryTest_MockCallableTypeHint;
use PHP73\MockeryTest_PartialAbstractClass;
use PHP73\MockeryTest_PartialAbstractClass2;
use PHP73\MockeryTest_PartialNormalClass;
use PHP73\MockeryTest_PartialNormalClass2;
use PHP73\MockeryTest_ReturnByRef;
use PHP73\MockeryTest_TestInheritedType;
use PHP73\MockeryTest_UnsetMethod;
use PHP73\MockeryTest_Wakeup1;
use PHP73\MockeryTest_WithProtectedAndPrivate;
use PHP73\MockeryTest_WithToString;
use PHP73\MockeryTestBar1;
use PHP73\MockeryTestFoo;
use PHP73\MockeryTestFoo2;
use PHP73\MockeryTestIsset_Bar;
use PHP73\MockeryTestIsset_Foo;
use PHP73\MockeryTestRef1;
use PHP81\MockeryTest_ClassThatImplementsSerializable;

use const PHP_MAJOR_VERSION;

use Redis;
use ReflectionClass;
use Serializable;
use Some\Thing\That\Doesnt\Exist;
use SplFileInfo;
use SplFixedArray;

use stdClass;

use Traversable;

/**
 * @coversDefaultClass \Mockery
 */
final class Container_Test extends Mockery_Test_Case
{
    /**
     * @return Generator<string,array{0: bool, 1: string}>
     */
    public static function provideIsValidClassNameCases(): iterable
    {
        yield from [
            'empty string' => [false, ''],
            'just a space' => [false, ' '],
            'class name with dot' => [false, 'ClassName.WithDot'],
            'too many backslashes' => [false, '\\\\TooManyBackSlashes'],
            'global class name' => [true, 'Foo'],
            'namespaced class name' => [true, Bar::class],
        ];
    }

    public function testBlockForwardingToPartialObject(): void
    {
        $m = \mock(new MockeryTestBar1(), [
            'foo' => 1,
            Container::BLOCKS => ['method1'],
        ]);
        self::assertSame($m, $m->method1());
    }

    /**
     * @issue issue/35
     */
    public function testCallingSelfOnlyReturnsLastMockCreatedOrCurrentMockBeingProgrammedSinceTheyAreOneAndTheSame(): void
    {
        $m = \mock(MockeryTestFoo::class);
        self::assertNotInstanceOf(MockeryTestFoo2::class, Mockery::self());
        //$m = mock('\PHP73\MockeryTestFoo2');
        //self::assertInstanceOf(MockeryTestFoo2::class, self());
        //$m = mock('\PHP73\MockeryTestFoo');
        //self::assertNotInstanceOf(MockeryTestFoo2::class, Mockery::self());
        //self::assertInstanceOf(MockeryTestFoo::class, Mockery::self());
    }

    public function testCanCreateNonOverridenInstanceOfPreviouslyOverridenInternalClasses(): void
    {
        if (PHP_MAJOR_VERSION > 7) {
            $this->expectException(LogicException::class);
        }

        Mockery::get_configuration()->set_internal_class_method_param_map(DateTime::class, 'modify', ['&$string']);
        // @ used to avoid E_STRICT for incompatible signature
        @$m = \mock(DateTime::class);
        self::assertInstanceOf(Mock_Interface::class, $m, 'Mocking failed, remove @ error suppresion to debug');
        $rc = new ReflectionClass($m);
        $rm = $rc->getMethod('modify');
        $params = $rm->getParameters();
        self::assertTrue($params[0]->isPassedByReference());

        Mockery::get_configuration()->reset_internal_class_method_param_maps();

        $m = \mock(DateTime::class);
        self::assertInstanceOf(Mock_Interface::class, $m, 'Mocking failed');
        $rc = new ReflectionClass($m);
        $rm = $rc->getMethod('modify');
        $params = $rm->getParameters();
        self::assertFalse($params[0]->isPassedByReference());

        Mockery::get_configuration()->reset_internal_class_method_param_maps();
    }

    public function testCanMockAbstractClassWithAbstractPublicMethod(): void
    {
        $m = \mock(MockeryTest_AbstractWithAbstractPublicMethod::class);
        self::assertInstanceOf(MockeryTest_AbstractWithAbstractPublicMethod::class, $m);
    }

    public function testCanMockAbstractWithAbstractProtectedMethod(): void
    {
        $m = \mock(MockeryTest_AbstractWithAbstractMethod::class);
        self::assertInstanceOf(MockeryTest_AbstractWithAbstractMethod::class, $m);
    }

    public function testCanMockClassAndApplyMultipleInterfaces(): void
    {
        $m = \mock('\PHP73\MockeryTestFoo, \PHP73\MockeryTest_Interface1, \PHP73\MockeryTest_Interface2');
        self::assertInstanceOf(MockeryTestFoo::class, $m);
        self::assertInstanceOf(MockeryTest_Interface1::class, $m);
        self::assertInstanceOf(MockeryTest_Interface2::class, $m);
    }

    public function testCanMockClassContainingAPublicWakeupMethod(): void
    {
        $m = \mock(MockeryTest_Wakeup1::class);
        self::assertInstanceOf(MockeryTest_Wakeup1::class, $m);
    }

    public function testCanMockClassContainingMagicCallMethod(): void
    {
        $m = \mock(MockeryTest_Call1::class);
        self::assertInstanceOf(MockeryTest_Call1::class, $m);
    }

    public function testCanMockClassContainingMagicCallMethodWithoutTypeHinting(): void
    {
        $m = \mock(MockeryTest_Call2::class);
        self::assertInstanceOf(MockeryTest_Call2::class, $m);
    }

    public function testCanMockClassUsingMagicCallMethodsInPlaceOfNormalMethods(): void
    {
        $m = Mockery::mock(Gateway::class);
        $m->should_receive('iDoSomethingReallyCoolHere')
            ->once();
        $m->iDoSomethingReallyCoolHere();
    }

    public function testCanMockClassWhereMethodHasReferencedParameter(): void
    {
        self::assertInstanceOf(Mock_Interface::class, Mockery::mock(new MockeryTest_MethodParamRef()));
    }

    public function testCanMockClassWithConstructor(): void
    {
        $m = \mock(MockeryTest_ClassConstructor::class);
        self::assertInstanceOf(MockeryTest_ClassConstructor::class, $m);
    }

    public function testCanMockClassWithConstructorNeedingClassArgs(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class);
        self::assertInstanceOf(MockeryTest_ClassConstructor2::class, $m);
    }

    public function testCanMockClassWithReservedWordMethod(): void
    {
        if (! \extension_loaded('redis')) {
            self::markTestSkipped('phpredis not installed');
        }

        self::assertInstanceOf(Redis::class, \mock(Redis::class));
    }

    public function testCanMockClassesThatDescendFromInternalClasses(): void
    {
        $mock = \mock(MockeryTest_ClassThatDescendsFromInternalClass::class);
        self::assertInstanceOf(DateTime::class, $mock);
    }

    /**
     * @requires PHP <8.1
     */
    public function testCanMockClassesThatImplementSerializable(): void
    {
        $mock = \mock(MockeryTest_ClassThatImplementsSerializable::class);
        self::assertInstanceOf(Serializable::class, $mock);
    }

    public function testCanMockInterface(): void
    {
        $m = \mock(MockeryTest_Interface::class);
        self::assertInstanceOf(MockeryTest_Interface::class, $m);
    }

    public function testCanMockInterfaceWithAbstractMethod(): void
    {
        $m = \mock(MockeryTest_InterfaceWithAbstractMethod::class);
        self::assertInstanceOf(MockeryTest_InterfaceWithAbstractMethod::class, $m);
        $m->should_receive('foo')
            ->and_return(1);
        self::assertSame(1, $m->foo());
    }

    public function testCanMockInterfaceWithPublicStaticMethod(): void
    {
        $m = \mock(MockeryTest_InterfaceWithPublicStaticMethod::class);
        self::assertInstanceOf(MockeryTest_InterfaceWithPublicStaticMethod::class, $m);
    }

    public function testCanMockInterfacesAlongsideTraversable(): void
    {
        $mock = \mock('\stdClass, \ArrayAccess, \Countable, \Traversable');
        self::assertInstanceOf(stdClass::class, $mock);
        self::assertInstanceOf(ArrayAccess::class, $mock);
        self::assertInstanceOf(Countable::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
    }

    public function testCanMockInterfacesExtendingTraversable(): void
    {
        $mock = \mock(MockeryTest_InterfaceWithTraversable::class);
        self::assertInstanceOf(MockeryTest_InterfaceWithTraversable::class, $mock);
        self::assertInstanceOf(ArrayAccess::class, $mock);
        self::assertInstanceOf(Countable::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
    }

    public function testCanMockInternalClassesThatImplementSerializable(): void
    {
        $mock = \mock(ArrayObject::class);
        self::assertInstanceOf(Serializable::class, $mock);
    }

    public function testCanMockMethodsWithRequiredParamsThatHaveDefaultValues(): void
    {
        $mock = \mock(MockeryTest_MethodWithRequiredParamWithDefaultValue::class);
        $mock->should_ignore_missing();
        self::assertNull($mock->foo(123, null));
    }

    public function testCanMockMultipleInterfaces(): void
    {
        $m = \mock('\PHP73\MockeryTest_Interface1, \PHP73\MockeryTest_Interface2');
        self::assertInstanceOf(MockeryTest_Interface1::class, $m);
        self::assertInstanceOf(MockeryTest_Interface2::class, $m);
    }

    public function testCanMockMultipleInterfacesThatMayNotExist(): void
    {
        $m = \mock(
            'NonExistingClass, \PHP73\MockeryTest_Interface1, \PHP73\MockeryTest_Interface2, \Some\Thing\That\Doesnt\Exist'
        );
        self::assertInstanceOf(MockeryTest_Interface1::class, $m);
        self::assertInstanceOf(MockeryTest_Interface2::class, $m);
        self::assertInstanceOf(Exist::class, $m);
    }

    public function testCanMockSpl(): void
    {
        $m = \mock(SplFixedArray::class);
        self::assertInstanceOf(SplFixedArray::class, $m);
    }

    public function testCanMockStaticMethods(): void
    {
        $m = \mock('alias:MyNamespace\MyClass2');
        $m->should_receive('staticFoo')
            ->and_return('bar');
        self::assertSame('bar', MyClass2::staticFoo());
    }

    /**
     * Real world version of
     * testCanOverrideExpectedParametersOfInternalPHPClassesToPreserveRefs
     */
    public function testCanOverrideExpectedParametersOfExtensionPHPClassesToPreserveRefs(): void
    {
        if (! \class_exists(MongoCollection::class, false)) {
            self::markTestSkipped('ext/mongo not installed');
        }
        if (PHP_MAJOR_VERSION > 7) {
            $this->expectException(LogicException::class);
        }
        Mockery::get_configuration()->set_internal_class_method_param_map(
            MongoCollection::class,
            'insert',
            ['&$data', '$options']
        );
        // @ used to avoid E_STRICT for incompatible signature
        @$m = \mock(MongoCollection::class);
        self::assertInstanceOf(Mock_Interface::class, $m, 'Mocking failed, remove @ error suppresion to debug');
        $m->should_receive('insert')
            ->with(Mockery::on(static function (&$data) {
                $data['_id'] = 123;
                return true;
            }), Mockery::type('array'));
        $data = [
            'a' => 1,
            'b' => 2,
        ];
        $m->insert($data, []);
        self::assertArrayHasKey('_id', $data);
        self::assertSame(123, $data['_id']);
        Mockery::get_configuration()->reset_internal_class_method_param_maps();
    }

    /**
     * Meant to test the same logic as
     * testCanOverrideExpectedParametersOfExtensionPHPClassesToPreserveRefs,
     * but:
     * - doesn't require an extension
     * - isn't actually known to be used
     */
    public function testCanOverrideExpectedParametersOfInternalPHPClassesToPreserveRefs(): void
    {
        if (PHP_MAJOR_VERSION > 7) {
            $this->expectException(LogicException::class);
        }

        Mockery::get_configuration()->set_internal_class_method_param_map(DateTime::class, 'modify', ['&$string']);
        // @ used to avoid E_STRICT for incompatible signature
        @$m = \mock(DateTime::class);
        self::assertInstanceOf(Mock_Interface::class, $m, 'Mocking failed, remove @ error suppresion to debug');
        $m->should_receive('modify')
            ->with(Mockery::on(static function (&$string) {
                $string = 'foo';
                return true;
            }));
        $data = 'bar';
        $m->modify($data);
        self::assertSame('foo', $data);
        Mockery::get_configuration()->reset_internal_class_method_param_maps();
    }

    public function testCanPartialMockObjectUsingMagicCallMethodsInPlaceOfNormalMethods(): void
    {
        $m = Mockery::mock(new Gateway());
        $m->should_receive('iDoSomethingReallyCoolHere')
            ->once();
        $m->iDoSomethingReallyCoolHere();
    }

    public function testCanPartiallyMockANormalClass(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass::class . '[foo]');
        self::assertInstanceOf(MockeryTest_PartialNormalClass::class, $m);
        $m->should_receive('foo')
            ->and_return('cba');
        self::assertSame('abc', $m->bar());
        self::assertSame('cba', $m->foo());
    }

    public function testCanPartiallyMockANormalClassWith2Methods(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass2::class . '[foo, baz]');
        self::assertInstanceOf(MockeryTest_PartialNormalClass2::class, $m);
        $m->should_receive('foo')
            ->and_return('cba');
        $m->should_receive('baz')
            ->and_return('cba');
        self::assertSame('abc', $m->bar());
        self::assertSame('cba', $m->foo());
        self::assertSame('cba', $m->baz());
    }

    public function testCanPartiallyMockAnAbstractClass(): void
    {
        $m = \mock(MockeryTest_PartialAbstractClass::class . '[foo]');
        self::assertInstanceOf(MockeryTest_PartialAbstractClass::class, $m);
        $m->should_receive('foo')
            ->and_return('cba');
        self::assertSame('abc', $m->bar());
        self::assertSame('cba', $m->foo());
    }

    public function testCanPartiallyMockAnAbstractClassWith2Methods(): void
    {
        $m = \mock(MockeryTest_PartialAbstractClass2::class . '[foo,baz]');
        self::assertInstanceOf(MockeryTest_PartialAbstractClass2::class, $m);
        $m->should_receive('foo')
            ->and_return('cba');
        $m->should_receive('baz')
            ->and_return('cba');
        self::assertSame('abc', $m->bar());
        self::assertSame('cba', $m->foo());
        self::assertSame('cba', $m->baz());
    }

    public function testCanPartiallyMockObjectWhereMethodHasReferencedParameter(): void
    {
        self::assertInstanceOf(Mock_Interface::class, Mockery::mock(new MockeryTest_MethodParamRef2()));
    }

    public function testCanUseBlacklistAndExpectionOnNonBlacklistedMethod(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass2::class . '[!foo]');
        $m->should_receive('bar')
            ->and_return('test')
            ->once();
        self::assertSame('test', $m->bar());
    }

    public function testCanUseEmptyMethodlist(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass2::class . '[]');
        self::assertInstanceOf(MockeryTest_PartialNormalClass2::class, $m);
    }

    public function testCanUseExclamationToBlacklistMethod(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass2::class . '[!foo]');
        self::assertSame('abc', $m->foo());
    }

    public function testCantCallMethodWhenUsingBlacklistAndNoExpectation(): void
    {
        $m = \mock(MockeryTest_PartialNormalClass2::class . '[!foo]');
        $this->expectException(Bad_Method_Call_Exception::class);
        $this->expectExceptionMessageMatches('/::bar\(\), but no expectations were specified/');
        $m->bar();
    }

    /**
     * @issue issue/21
     */
    public function testClassDeclaringIssetDoesNotThrowException(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_IssetMethod::class));
    }

    /**
     * @issue issue/21
     */
    public function testClassDeclaringUnsetDoesNotThrowException(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_UnsetMethod::class));
    }

    public function testClassesWithFinalMethodsCanBeProperPartialMocks(): void
    {
        $m = \mock(MockeryFoo4::class . '[bar]');
        $m->should_receive('bar')
            ->and_return('baz');
        self::assertSame('baz', $m->foo());
        self::assertSame('baz', $m->bar());
        self::assertInstanceOf(MockeryFoo4::class, $m);
    }

    public function testClassesWithFinalMethodsCanBeProperPartialMocksButFinalMethodsNotPartialed(): void
    {
        $m = \mock(MockeryFoo4::class . '[foo]');
        $m->should_receive('foo')
            ->and_return('foo');
        self::assertSame('baz', $m->foo()); // partial expectation ignored - will fail callcount assertion
        self::assertInstanceOf(MockeryFoo4::class, $m);
    }

    public function testClassesWithFinalMethodsCanBeProxyPartialMocks(): void
    {
        $m = \mock(new MockeryFoo4());
        $m->should_receive('foo')
            ->and_return('baz');
        self::assertSame('baz', $m->foo());
        self::assertSame('bar', $m->bar());
        self::assertInstanceOf(MockeryFoo4::class, $m);
    }

    public function testCreatingAPartialAllowsDynamicExpectationsAndPassesThroughUnexpectedMethods(): void
    {
        $m = \mock(new MockeryTestFoo());
        $m->should_receive('bar')
            ->and_return('bar');
        self::assertSame('bar', $m->bar());
        self::assertSame('foo', $m->foo());
        self::assertInstanceOf(MockeryTestFoo::class, $m);
    }

    public function testCreatingAPartialAllowsExpectationsToInterceptCallsToImplementedMethods(): void
    {
        $m = \mock(new MockeryTestFoo2());
        $m->should_receive('bar')
            ->and_return('baz');
        self::assertSame('baz', $m->bar());
        self::assertSame('foo', $m->foo());
        self::assertInstanceOf(MockeryTestFoo2::class, $m);
    }

    /**
     * @issue issue/89
     */
    public function testCreatingMockOfClassWithExistingToStringMethodDoesntCreateClassWithTwoToStringMethods(): void
    {
        $m = \mock(MockeryTest_WithToString::class); // this would fatal
        $m->should_receive('__toString')
            ->and_return('dave');
        self::assertSame('dave', "{$m}");
    }

    public function testCreationOfInstanceMock(): void
    {
        $m = \mock('overload:MyNamespace\MyClass4');
        self::assertInstanceOf(MyClass4::class, $m);
    }

    public function testCreationOfInstanceMockWithFullyQualifiedName(): void
    {
        $m = \mock('overload:\MyNamespace\MyClass11');
        self::assertInstanceOf(MyClass11::class, $m);
    }

    public function testExceptionOutputMakesBooleansLookLikeBooleans(): void
    {
        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with(123);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage('MyTestClass::foo(true, false, [0 => true, 1 => false])');

        $mock->foo(true, false, [true, false]);
    }

    public function testExistingStaticMethodMocking(): void
    {
        $mock = \mock('\PHP73\MockeryTest_PartialStatic[mockMe]');

        $mock->should_receive('mockMe')
            ->with(5)
            ->and_return(10);

        self::assertSame(10, $mock::mockMe(5));
        self::assertSame(3, $mock::keepMe(3));
    }

    public function testFinalClassesCanBePartialMocks(): void
    {
        $m = \mock(new MockeryFoo3());
        $m->should_receive('foo')
            ->and_return('baz');
        self::assertSame('baz', $m->foo());
        self::assertNotInstanceOf(MockeryFoo3::class, $m);
    }

    public function testGetExpectationCountFreshContainer(): void
    {
        self::assertSame(0, Mockery::get_container()->mockery_get_expectation_count());
    }

    public function testGetExpectationCountMockWithAtLeast(): void
    {
        $m = \mock();
        $m->should_receive('foo')
            ->at_least()
            ->once();
        self::assertSame(1, Mockery::get_container()->mockery_get_expectation_count());
        $m->foo();
        $m->foo();
    }

    public function testGetExpectationCountMockWithNever(): void
    {
        $m = \mock();
        $m->should_receive('foo')
            ->never();
        self::assertSame(1, Mockery::get_container()->mockery_get_expectation_count());
    }

    public function testGetExpectationCountMockWithOnce(): void
    {
        $m = \mock();
        $m->should_receive('foo')
            ->once();
        self::assertSame(1, Mockery::get_container()->mockery_get_expectation_count());
        $m->foo();
    }

    public function testGetExpectationCountStub(): void
    {
        $m = \mock();
        $m->should_receive('foo');
        self::assertSame(0, Mockery::get_container()->mockery_get_expectation_count());
    }

    public function testGetKeyOfDemeterMockShouldReturnKeyWhenMatchingMock(): void
    {
        $m = \mock();
        $m->should_receive('foo->bar');
        self::assertMatchesRegularExpression(
            '/Mockery_(\d+)__demeter_([0-9a-f]+)_foo/',
            Mockery::get_container()->get_key_of_demeter_mock_for('foo', \get_class($m))
        );
    }

    public function testGetKeyOfDemeterMockShouldReturnNullWhenNoMatchingMock(): void
    {
        $method = 'unknownMethod';
        self::assertNull(Mockery::get_container()->get_key_of_demeter_mock_for($method, 'any'));

        $m = \mock();
        $m->should_receive('method');
        self::assertNull(Mockery::get_container()->get_key_of_demeter_mock_for($method, \get_class($m)));

        $m->should_receive('foo->bar');
        self::assertNull(Mockery::get_container()->get_key_of_demeter_mock_for($method, \get_class($m)));
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithCircularArray(): void
    {
        $testArray = [];
        $testArray['myself'] = &$testArray;

        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage("MyTestClass::foo(['myself' => [...]])");
        $mock->foo($testArray);
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithNestedArray(): void
    {
        $testArray = [];
        $testArray['a_scalar'] = 2;
        $testArray['an_array'] = [1, 2, 3];

        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage("MyTestClass::foo(['a_scalar' => 2, 'an_array' => [...]])");
        $mock->foo($testArray);
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithNestedClosure(): void
    {
        $testArray = [];
        $testArray['a_scalar'] = 2;
        $testArray['a_closure'] = static function (): void {
        };

        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage("MyTestClass::foo(['a_scalar' => 2, 'a_closure' => object(Closure");
        $mock->foo($testArray);
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithNestedObject(): void
    {
        $testArray = [];
        $testArray['a_scalar'] = 2;
        $testArray['an_object'] = new stdClass();

        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage("MyTestClass::foo(['a_scalar' => 2, 'an_object' => object(stdClass)])");
        $mock->foo($testArray);
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithNestedResource(): void
    {
        $testArray = [];
        $testArray['a_scalar'] = 2;
        $testArray['a_resource'] = \fopen('php://memory', 'r');

        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage("MyTestClass::foo(['a_scalar' => 2, 'a_resource' => resource(...)])");
        $mock->foo($testArray);
    }

    public function testHandlesMethodWithArgumentExpectationWhenCalledWithResource(): void
    {
        $mock = \mock('MyTestClass');
        $mock->should_receive('foo')
            ->with([
                'yourself' => 21,
            ]);

        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->expectExceptionMessage('MyTestClass::foo(resource(...))');
        $mock->foo(\fopen('php://memory', 'r'));
    }

    public function testInstanceMocksShouldIgnoreMissing(): void
    {
        $m = \mock('overload:MyNamespace\MyClass12');
        $m->should_ignore_missing();

        $instance = new MyClass12();
        self::assertNull($instance->foo());
    }

    public function testInstantiationOfInstanceMock(): void
    {
        $m = \mock('overload:MyNamespace\MyClass5');
        $instance = new MyClass5();
        self::assertInstanceOf(MyClass5::class, $instance);
    }

    public function testInstantiationOfInstanceMockImportsDefaultExpectations(): void
    {
        $m = \mock('overload:MyNamespace\MyClass6');
        $m->should_receive('foo')
            ->and_return('bar')
            ->by_default();
        $instance = new MyClass6();

        self::assertSame('bar', $instance->foo());
    }

    public function testInstantiationOfInstanceMockImportsDefaultExpectationsInTheCorrectOrder(): void
    {
        $m = \mock('overload:MyNamespace\MyClass6');
        $m->should_receive('foo')
            ->and_return(1)
            ->by_default();
        $m->should_receive('foo')
            ->and_return(2)
            ->by_default();
        $m->should_receive('foo')
            ->and_return(3)
            ->by_default();
        $instance = new MyClass6();

        self::assertSame(3, $instance->foo());
    }

    public function testInstantiationOfInstanceMockImportsExpectations(): void
    {
        $m = \mock('overload:MyNamespace\MyClass6');
        $m->should_receive('foo')
            ->and_return('bar');
        $instance = new MyClass6();
        self::assertSame('bar', $instance->foo());
    }

    public function testInstantiationOfInstanceMockWithConstructorParameterValidation(): void
    {
        $m = \mock('overload:MyNamespace\MyClass14');
        $params = [
            'value1' => \uniqid('test_', false),
        ];
        $m->should_receive('__construct')
            ->with($params)
            ->once();

        new MyClass14($params);
    }

    public function testInstantiationOfInstanceMockWithConstructorParameterValidationException(): void
    {
        $m = \mock('overload:MyNamespace\MyClass16');
        $m->should_receive('__construct')
            ->and_throw(new Exception('instance_mock ' . \random_int(100, 999)));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^instance_mock \d{3}$/');
        new MyClass16();
    }

    public function testInstantiationOfInstanceMockWithConstructorParameterValidationNegative(): void
    {
        $m = \mock('overload:MyNamespace\MyClass15');
        $params = [
            'value1' => \uniqid('test_', false),
        ];
        $m->should_receive('__construct')
            ->with($params);

        $this->expectException(No_Matching_Expectation_Exception::class);
        new MyClass15([]);
    }

    public function testInstantiationOfInstanceMocksAddsThemToContainerForVerification(): void
    {
        $m = \mock('overload:MyNamespace\MyClass8');
        $m->should_receive('foo')
            ->once();
        $instance = new MyClass8();
        $this->expectException(Mockery\Count_Validator\Exception::class);
        Mockery::close();
    }

    public function testInstantiationOfInstanceMocksDoesNotHaveCountValidatorCrossover(): void
    {
        $m = \mock('overload:MyNamespace\MyClass9');
        $m->should_receive('foo')
            ->once();
        $instance1 = new MyClass9();
        $instance2 = new MyClass9();
        $instance1->foo();
        $instance2->foo();
    }

    public function testInstantiationOfInstanceMocksDoesNotHaveCountValidatorCrossover2(): void
    {
        $m = \mock('overload:MyNamespace\MyClass10');
        $m->should_receive('foo')
            ->once();
        $instance1 = new MyClass10();
        $instance2 = new MyClass10();
        $instance1->foo();
        $this->expectException(Mockery\Count_Validator\Exception::class);
        Mockery::close();
    }

    public function testInstantiationOfInstanceMocksIgnoresVerificationOfOriginMock(): void
    {
        $m = \mock('overload:MyNamespace\MyClass7');
        $m->should_receive('foo')
            ->once()
            ->and_return('bar');
    }

    public function testInterfacesCanHaveAssertions(): void
    {
        $m = \mock('\stdClass, \ArrayAccess, \Countable, \Traversable');
        $m->should_receive('foo')
            ->once();
        $m->foo();
    }

    /**
     * @dataProvider provideIsValidClassNameCases
     */
    public function testIsValidClassName($expected, $className): void
    {
        self::assertSame($expected, (new Container())->is_valid_class_name($className));
    }

    public function testIssetMappingUsingProxiedPartialsCheckNoExceptionThrown(): void
    {
        $var = \mock(new MockeryTestIsset_Bar());
        $mock = \mock(new MockeryTestIsset_Foo($var));
        $mock->should_receive('bar')
            ->once();
        $mock->bar();
        Mockery::close();

        self::assertTrue(true);
    }

    public function testMethodParamsPassedByReferenceHaveReferencePreserved(): void
    {
        $m = \mock(MockeryTestRef1::class);
        $m->should_receive('foo')
            ->with(Mockery::on(static function (&$a) {
                ++$a;
                return true;
            }), Mockery::any());
        $a = 1;
        $b = 1;
        $m->foo($a, $b);
        self::assertSame(2, $a);
        self::assertSame(1, $b);
    }

    public function testMethodParamsPassedByReferenceThroughWithArgsHaveReferencePreserved(): void
    {
        $m = \mock(MockeryTestRef1::class);
        $m->should_receive('foo')
            ->with_args(static function (&$a, $b) {
                ++$a;
                ++$b;
                return true;
            });
        $a = 1;
        $b = 1;
        $m->foo($a, $b);
        self::assertSame(2, $a);
        self::assertSame(1, $b);
    }

    public function testMethodsReturningParamsByReferenceDoesNotErrorOut(): void
    {
        \mock(MockeryTest_ReturnByRef::class);
        $mock = \mock(MockeryTest_ReturnByRef::class);
        $mock->should_receive('get')
            ->and_return($var = 123);
        self::assertSame($var, $mock->get());
    }

    public function testMockCallableTypeHint(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_MockCallableTypeHint::class));
    }

    public function testMockedStaticMethodsObeyMethodCounting(): void
    {
        $m = \mock('alias:MyNamespace\MyClass3');
        $m->should_receive('staticFoo')
            ->once()
            ->and_return('bar');
        $this->expectException(Mockery\Count_Validator\Exception::class);
        Mockery::close();
    }

    public function testMockedStaticThrowsExceptionWhenMethodDoesNotExist(): void
    {
        $m = \mock('alias:MyNamespace\StaticNoMethod');

        try {
            StaticNoMethod::staticFoo();
        } catch (Bad_Method_Call_Exception $e) {
            // Mockery + PHPUnit has a fail safe for tests swallowing our
            // exceptions
            $e->dismiss();
            self::assertTrue($e->dismissed());
            return;
        }

        self::fail('Exception was not thrown');
    }

    public function testMockeryCloseForIllegalIssetFileInclude(): void
    {
        $m = Mockery::mock(stdClass::class)
            ->should_receive('get')
            ->and_return(false)
            ->get_mock();

        $m->get();
        Mockery::close();

        // no idea what this test does, adding this as an assertion...
        self::assertTrue(true);
    }

    public function testMockeryDoesntTryAndMockLowercaseToString(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_Lowercase_ToString::class));
    }

    public function testMockeryShouldCallConstructorByDefaultWhenRequestingPartials(): void
    {
        $mock = \mock('\PHP73\EmptyConstructorTest[foo]');
        self::assertSame(0, $mock->numberOfConstructorArgs);
    }

    public function testMockeryShouldDistinguishBetweenConstructorParamsAndClosures(): void
    {
        $obj = new MockeryTestFoo();
        self::assertInstanceOf(Mock_Interface::class, \mock('\PHP73\MockeryTest_ClassMultipleConstructorParams[dave]', [
            &$obj, 'foo',
        ]));
    }

    public function testMockeryShouldInterpretEmptyArrayAsConstructorArgs(): void
    {
        $mock = \mock(EmptyConstructorTest::class, []);
        self::assertSame(0, $mock->numberOfConstructorArgs);
    }

    public function testMockeryShouldNotMockCallstaticMagicMethod(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_CallStatic::class));
    }

    public function testMockeryShouldRespectInterfaceWithMethodParamSelf(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryTest_InterfaceWithMethodParamSelf::class));
    }

    public function testMockingAConcreteObjectCreatesAPartialWithoutError(): void
    {
        $m = \mock(new stdClass());
        $m->should_receive('foo')
            ->and_return('bar');
        self::assertSame('bar', $m->foo());
        self::assertInstanceOf(stdClass::class, $m);
    }

    public function testMockingAKnownConcreteClassCanBeGrantedAnArbitraryClassType(): void
    {
        $m = \mock('alias:MyNamespace\MyClass');
        $m->should_receive('foo')
            ->and_return('bar');

        self::assertSame('bar', $m->foo());
        self::assertInstanceOf(MyClass::class, $m);
    }

    public function testMockingAKnownConcreteClassSoMockInheritsClassType(): void
    {
        $m = \mock(stdClass::class);
        $m->should_receive('foo')
            ->and_return('bar');
        self::assertSame('bar', $m->foo());
        self::assertInstanceOf(stdClass::class, $m);
    }

    public function testMockingAKnownConcreteClassWithFinalMethodsThrowsNoException(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock(MockeryFoo4::class));
    }

    public function testMockingAKnownConcreteFinalClassThrowsErrorsOnlyPartialMocksCanMockFinalElements(): void
    {
        $this->expectException(Mockery\Exception::class);
        $m = \mock(MockeryFoo3::class);
    }

    public function testMockingAKnownUserClassSoMockInheritsClassType(): void
    {
        $m = \mock(MockeryTest_TestInheritedType::class);
        self::assertInstanceOf(MockeryTest_TestInheritedType::class, $m);
    }

    public function testMockingAllowsPublicPropertyStubbingOnNamedMock(): void
    {
        $m = \mock('Foo');
        $m->foo = 'bar';
        self::assertSame('bar', $m->foo);
        //self::assertArrayHasKey('foo', $m->mockery_get_mockable_properties());
    }

    public function testMockingAllowsPublicPropertyStubbingOnPartials(): void
    {
        $m = \mock(new stdClass());
        $m->foo = 'bar';
        self::assertSame('bar', $m->foo);
        //self::assertArrayHasKey('foo', $m->mockery_get_mockable_properties());
    }

    public function testMockingAllowsPublicPropertyStubbingOnRealClass(): void
    {
        $m = \mock(MockeryTestFoo::class);
        $m->foo = 'bar';
        self::assertSame('bar', $m->foo);
        //self::assertArrayHasKey('foo', $m->mockery_get_mockable_properties());
    }

    public function testMockingDoesNotStubNonStubbedPropertiesOnPartials(): void
    {
        $m = \mock(new MockeryTest_ExistingProperty());
        self::assertSame('bar', $m->foo);
        self::assertArrayNotHasKey('foo', $m->mockery_get_mockable_properties());
    }

    public function testMockingInterfaceThatExtendsIteratorAggregateDoesNotImplementIterator(): void
    {
        $mock = \mock(MockeryTest_InterfaceThatExtendsIteratorAggregate::class);
        self::assertInstanceOf(IteratorAggregate::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
        self::assertNotInstanceOf(Iterator::class, $mock);
    }

    public function testMockingInterfaceThatExtendsIteratorDoesNotImplementIterator(): void
    {
        $mock = \mock(MockeryTest_InterfaceThatExtendsIterator::class);
        self::assertInstanceOf(Iterator::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
    }

    /**
     * @requires PHP < 8.0
     */
    public function testMockingIteratorAggregateDoesNotImplementIterator(): void
    {
        $mock = \mock(MockeryTest_ImplementsIteratorAggregate::class);
        self::assertInstanceOf(IteratorAggregate::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
        self::assertNotInstanceOf(Iterator::class, $mock);
    }

    public function testMockingIteratorAggregateDoesNotImplementIteratorAlongside(): void
    {
        $mock = \mock(IteratorAggregate::class);
        self::assertInstanceOf(IteratorAggregate::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
        self::assertNotInstanceOf(Iterator::class, $mock);
    }

    /**
     * @requires PHP < 8.0
     */
    public function testMockingIteratorDoesNotImplementIterator(): void
    {
        $mock = \mock(MockeryTest_ImplementsIterator::class);
        self::assertInstanceOf(Iterator::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
    }

    public function testMockingIteratorDoesNotImplementIteratorAlongside(): void
    {
        $mock = \mock(Iterator::class);
        self::assertInstanceOf(Iterator::class, $mock);
        self::assertInstanceOf(Traversable::class, $mock);
    }

    public function testMockingPhpredisExtensionClassWorks(): void
    {
        if (! \class_exists('Redis')) {
            self::markTestSkipped('PHPRedis extension required for this test');
        }
        self::assertInstanceOf(Redis::class, \mock(Redis::class));
    }

    public function testNamedMockMultipleInterfaces(): void
    {
        $m = \mock('\stdClass, \ArrayAccess, \Countable', [
            'foo' => 1,
            'bar' => 2,
        ]);
        self::assertSame(1, $m->foo());
        self::assertSame(2, $m->bar());
        try {
            $m->f();
        } catch (Bad_Method_Call_Exception $e) {
            self::assertTrue((bool) \preg_match('/stdClass/', $e->getMessage()));
            self::assertTrue((bool) \preg_match('/ArrayAccess/', $e->getMessage()));
            self::assertTrue((bool) \preg_match('/Countable/', $e->getMessage()));
        }
    }

    public function testNamedMockWithArrayDefs(): void
    {
        $m = \mock(__FUNCTION__, [
            'foo' => 1,
            'bar' => 2,
        ]);
        self::assertSame(1, $m->foo());
        self::assertSame(2, $m->bar());
        try {
            $m->f();
        } catch (Bad_Method_Call_Exception $e) {
            self::assertStringContainsString(__FUNCTION__, $e->getMessage());
        }
    }

    public function testNamedMockWithArrayDefsCanBeOverridden(): void
    {
        // eg. In shared test setup
        $m = \mock(__FUNCTION__, [
            'foo' => 1,
        ]);

        // and then overridden in one test
        $m->should_receive('foo')
            ->with('bar')
            ->once()
            ->and_return(2);

        self::assertSame(2, $m->foo('bar'));

        try {
            $m->f();
        } catch (Bad_Method_Call_Exception $e) {
            self::assertStringContainsString(__FUNCTION__, $e->getMessage());
        }
    }

    public function testNamedMockWithConstructorArgs(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class . '[foo]', [$param1 = new stdClass()]);
        $m->should_receive('foo')
            ->and_return(123);
        self::assertSame(123, $m->foo());
        self::assertSame($param1, $m->getParam1());
    }

    public function testNamedMockWithConstructorArgsAndArrayDefs(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class . '[foo]', [$param1 = new stdClass()], [
            'foo' => 123,
        ]);
        self::assertSame(123, $m->foo());
        self::assertSame($param1, $m->getParam1());
    }

    public function testNamedMockWithConstructorArgsButNoQuickDefsShouldLeaveConstructorIntact(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class, [$param1 = new stdClass()]);
        $m->make_partial();
        self::assertSame($param1, $m->getParam1());
    }

    public function testNamedMockWithConstructorArgsWithInternalCallToMockedMethod(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class . '[foo]', [$param1 = new stdClass()]);
        $m->should_receive('foo')
            ->and_return(123);
        self::assertSame(123, $m->bar());
    }

    public function testNamedMockWithMakePartial(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class, [$param1 = new stdClass()]);
        $m->make_partial();
        self::assertSame('foo', $m->bar());
        $m->should_receive('bar')
            ->and_return(123);
        self::assertSame(123, $m->bar());
    }

    public function testNamedMockWithMakePartialThrowsIfNotAvailable(): void
    {
        $m = \mock(MockeryTest_ClassConstructor2::class, [$param1 = new stdClass()]);
        $m->make_partial();
        $this->expectException(\BadMethodCallException::class);
        $m->foorbar123();
        $m->mockery_verify();
    }

    public function testNamedMocksAddNameToExceptions(): void
    {
        $m = \mock(__FUNCTION__);
        $m->should_receive('foo')
            ->with(1)
            ->and_return('bar');
        try {
            $m->foo();
        } catch (Mockery\Exception $e) {
            self::assertStringContainsString(__FUNCTION__, $e->getMessage());
        }
    }

    public function testPartialWithArrayDefs(): void
    {
        $m = \mock(new MockeryTestBar1(), [
            'foo' => 1,
            Container::BLOCKS => ['method1'],
        ]);
        self::assertSame(1, $m->foo());
    }

    public function testPassingClosureAsFinalParameterUsedToDefineExpectations(): void
    {
        $m = \mock('foo', static function ($m): void {
            $m->should_receive('foo')
                ->once()
                ->and_return('bar');
        });
        self::assertSame('bar', $m->foo());
    }

    public function testSettingPropertyOnInstanceMockWillSetItOnActualInstance(): void
    {
        $m = \mock('overload:MyNamespace\MyClass13');
        $m->should_receive('foo')
            ->and_set('bar', 'baz');
        $instance = new MyClass13();
        $instance->foo();
        self::assertSame('baz', $m->bar);
        self::assertSame('baz', $instance->bar);
    }

    public function testShouldThrowIfAttemptingToStubPrivateMethod(): void
    {
        $mock = \mock(MockeryTest_WithProtectedAndPrivate::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('privateMethod() cannot be mocked as it is a private method');
        $mock->should_receive('privateMethod');
    }

    public function testShouldThrowIfAttemptingToStubProtectedMethod(): void
    {
        $mock = \mock(MockeryTest_WithProtectedAndPrivate::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'protectedMethod() cannot be mocked as it is a protected method and mocking protected methods is not enabled for the currently used mock object.'
        );
        $mock->should_receive('protectedMethod');
    }

    public function testSimpleMockWithArrayDefs(): void
    {
        $m = \mock([
            'foo' => 1,
            'bar' => 2,
        ]);
        self::assertSame(1, $m->foo());
        self::assertSame(2, $m->bar());
    }

    public function testSimpleMockWithArrayDefsCanBeOverridden(): void
    {
        // eg. In shared test setup
        $m = \mock([
            'foo' => 1,
            'bar' => 2,
        ]);

        // and then overridden in one test
        $m->should_receive('foo')
            ->with('baz')
            ->once()
            ->and_return(2);
        $m->should_receive('bar')
            ->with('baz')
            ->once()
            ->and_return(42);

        self::assertSame(2, $m->foo('baz'));
        self::assertSame(42, $m->bar('baz'));
    }

    public function testSimplestMockCreation(): void
    {
        $m = \mock();
        $m->should_receive('foo')
            ->and_return('bar');
        self::assertSame('bar', $m->foo());
    }

    public function testSplClassWithFinalMethodsCanBeMocked(): void
    {
        $m = \mock(SplFileInfo::class);
        $m->should_receive('foo')
            ->and_return('baz');
        self::assertSame('baz', $m->foo());
        self::assertInstanceOf(SplFileInfo::class, $m);
    }

    public function testSplClassWithFinalMethodsCanBeMockedMultipleTimes(): void
    {
        \mock(SplFileInfo::class);
        $m = \mock(SplFileInfo::class);
        $m->should_receive('foo')
            ->and_return('baz');
        self::assertSame('baz', $m->foo());
        self::assertInstanceOf(SplFileInfo::class, $m);
    }

    public function testSplfileinfoClassMockPassesUserExpectations(): void
    {
        $file = \mock('\SplFileInfo[getFilename,getPathname,getExtension,getMTime]', [__FILE__]);
        $file->should_receive('getFilename')
            ->once()
            ->and_return('foo');
        $file->should_receive('getPathname')
            ->once()
            ->and_return('path/to/foo');
        $file->should_receive('getExtension')
            ->once()
            ->and_return('css');
        $file->should_receive('getMTime')
            ->once()
            ->and_return(\time());

        // not sure what this test is for, maybe something special about
        // SplFileInfo
        self::assertSame('foo', $file->getFilename());
        self::assertSame('path/to/foo', $file->getPathname());
        self::assertSame('css', $file->getExtension());
        self::assertIsInt($file->getMTime());
    }

    public function testThrowsExceptionIfClassNamesContainInvalidCharacters(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Class name contains invalid characters');

        $container = new Container();

        self::assertInstanceOf(Mock_Interface::class, $container->mock('ClassName.WithDot'));
    }

    public function testThrowsExceptionIfClassOrInterfaceForPartialMockDoesNotExist(): void
    {
        $this->expectException(Mockery\Exception::class);

        \mock('PartialNormalClassXYZ[foo]');
    }

    public function testThrowsExceptionIfSettingExpectationForNonMockedMethodOfPartialMock(): void
    {
        self::markTestSkipped('For now...');
        $m = \mock(MockeryTest_PartialNormalClass::class . '[foo]');
        self::assertInstanceOf(MockeryTest_PartialNormalClass::class, $m);
        $this->expectException(Mockery\Exception::class);
        $m->should_receive('bar')
            ->and_return('cba');
    }

    public function testThrowsWhenNamedMockClassExistsAndIsNotMockery(): void
    {
        $builder = new Mock_Configuration_Builder();
        $builder->set_name('DateTime');
        $this->expectException(\Mockery\Exception\Runtime_Exception::class);
        $this->expectExceptionMessage('Could not load mock DateTime, class already exists');
        $mock = \mock($builder);
    }

    public function testUndeclaredClassIsDeclared(): void
    {
        self::assertFalse(\class_exists('BlahBlah'));
        $mock = \mock('BlahBlah');
        self::assertInstanceOf('\BlahBlah', $mock);
    }

    public function testUndeclaredClassWithNamespaceIncludingLeadingOperatorIsDeclared(): void
    {
        self::assertFalse(\class_exists('\MyClasses\DaveBlah\BlahBlah'));
        $mock = \mock('\MyClasses\DaveBlah\BlahBlah');
        self::assertInstanceOf('\MyClasses\DaveBlah\BlahBlah', $mock);
    }

    public function testUndeclaredClassWithNamespaceIsDeclared(): void
    {
        self::assertFalse(\class_exists('MyClasses\Blah\BlahBlah'));
        $mock = \mock('MyClasses\Blah\BlahBlah');
        self::assertInstanceOf('\MyClasses\Blah\BlahBlah', $mock);
    }

    public function testWakeupMagicIsNotMockedToAllowSerialisationInstanceHack(): void
    {
        self::assertInstanceOf(DateTime::class, \mock(DateTime::class));
    }
}
