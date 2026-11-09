<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use Mockery\Exception\Bad_Method_Call_Exception;
use Error;
use Mockery\Exception as Mockery_Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Container;
use Mockery\Count_Validator\Exception as Count_Validator_Exception;
use Mockery\Exception\Invalid_Count_Exception;
use Mockery\Exception\No_Matching_Expectation_Exception;
use Mockery\Mock_Interface;
use Mockery\Undefined;
use OutOfBoundsException;
use PHP73\IWater;
use PHP73\Mockery_Demeterowski;
use PHP73\Mockery_Duck;
use PHP73\Mockery_Duck_Nonswimmer;
use PHP73\Mockery_Magic;
use PHP73\Mockery_UseDemeter;
use PHP73\MockeryTest_Foo;
use PHP73\MockeryTest_InterMethod1;
use PHP73\MockeryTest_SubjectCall1;
use PHP73\MyService2;
use stdClass;

/**
 * @coversDefaultClass \Mockery
 */
final class Expectation_Test extends Mockery_Test_Case
{
    protected $mock;

    protected function mockery_test_set_up(): void
    {
        $this->mock = \mock('Foo');
    }

    public function mockery_test_tear_down(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(true);
    }

    public function testAnExampleWithSomeExpectationAmends(): void
    {
        $service = \mock('MyService');
        $service->should_receive('login')
            ->with('user', 'pass')
            ->once()
            ->and_return(true);
        $service->should_receive('hasBookmarksTagged')
            ->with('php')
            ->once()
            ->and_return(false);
        $service->should_receive('addBookmark')
            ->with(Mockery::pattern('/^http:/'), Mockery::type('string'))->times(3)->and_return(true);
        $service->should_receive('hasBookmarksTagged')
            ->with('php')
            ->once()
            ->and_return(true);

        self::assertTrue($service->login('user', 'pass'));
        self::assertFalse($service->hasBookmarksTagged('php'));
        self::assertTrue($service->addBookmark('http://example.com/1', 'some_tag1'));
        self::assertTrue($service->addBookmark('http://example.com/2', 'some_tag2'));
        self::assertTrue($service->addBookmark('http://example.com/3', 'some_tag3'));
        self::assertTrue($service->hasBookmarksTagged('php'));
    }

    public function testAnExampleWithSomeExpectationAmendsOnCallCounts(): void
    {
        $service = \mock('MyService');
        $service->should_receive('login')
            ->with('user', 'pass')
            ->once()
            ->and_return(true);
        $service->should_receive('hasBookmarksTagged')
            ->with('php')
            ->once()
            ->and_return(false);
        $service->should_receive('addBookmark')
            ->with(Mockery::pattern('/^http:/'), Mockery::type('string'))->times(3)->and_return(true);
        $service->should_receive('hasBookmarksTagged')
            ->with('php')
            ->twice()
            ->and_return(true);

        self::assertTrue($service->login('user', 'pass'));
        self::assertFalse($service->hasBookmarksTagged('php'));
        self::assertTrue($service->addBookmark('http://example.com/1', 'some_tag1'));
        self::assertTrue($service->addBookmark('http://example.com/2', 'some_tag2'));
        self::assertTrue($service->addBookmark('http://example.com/3', 'some_tag3'));
        self::assertTrue($service->hasBookmarksTagged('php'));
        self::assertTrue($service->hasBookmarksTagged('php'));
    }

    public function testAnExampleWithSomeExpectationAmendsOnCallCountsPHPUnitTest(): void
    {
        /**  Mock_Interface $service */
        $service = \mock(MyService2::class);

        $service->expects('login')
            ->once()
            ->with('user', 'pass')
            ->and_return_true();

        $service->expects('hasBookmarksTagged')
            ->times(3)
            ->with('php')
            ->and_returns(false, true, true);

        $service->expects('addBookmark')
            ->times(3)
            ->and_return_true();

        self::assertTrue($service->login('user', 'pass'));
        self::assertFalse($service->hasBookmarksTagged('php'));
        self::assertTrue($service->addBookmark('http://example.com/1', 'some_tag1'));
        self::assertTrue($service->addBookmark('http://example.com/2', 'some_tag2'));
        self::assertTrue($service->addBookmark('http://example.com/3', 'some_tag3'));
        self::assertTrue($service->hasBookmarksTagged('php'));
        self::assertTrue($service->hasBookmarksTagged('php'));
    }

    public function testAndAnyOtherConstraintDoesNotPreventMatchingOfRegularArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with(1, 2, Mockery::and_any_others());
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(10, 2, 3, 4, 5);
        Mockery::close();
    }

    public function testAndAnyOtherConstraintMatchesTheRestOfTheArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with(1, 2, Mockery::and_any_others())->twice();
        $this->mock->foo(1, 2, 3, 4, 5);
        $this->mock->foo(1, 2, 'str', 3, 4);
    }

    public function testAndAnyOtherConstraintMultipleExpectationsButNoOthers(): void
    {
        $this->mock->should_receive('foo')
            ->with('a', Mockery::and_any_others())->and_return('a');
        $this->mock->should_receive('foo')
            ->with('b', Mockery::and_any_others())->and_return('b');
        self::assertSame('a', $this->mock->foo('a'));
        self::assertSame('b', $this->mock->foo('b'));
    }

    public function testAndThrowExceptions(): void
    {
        $this->mock->should_receive('foo')
            ->and_throw_exceptions([new OutOfBoundsException(), new InvalidArgumentException()]);

        try {
            $this->mock->foo();
            throw new \Exception('Expected OutOfBoundsException, non thrown');
        } catch (\Exception $e) {
            self::assertInstanceOf('OutOfBoundsException', $e, "Wrong or no exception thrown: {$e->getMessage()}");
        }

        try {
            $this->mock->foo();
            throw new \Exception('Expected InvalidArgumentException, non thrown');
        } catch (\Exception $e) {
            self::assertInstanceOf('InvalidArgumentException', $e, "Wrong or no exception thrown: {$e->getMessage()}");
        }
    }

    public function testAndThrowExceptionsCatchNonExceptionArgument(): void
    {
        $this->expectException(\Mockery\Exception::class);
        $this->expectExceptionMessage('You must pass an array of exception objects to and_throw_exceptions');

        $this->mock
            ->should_receive('foo')
            ->and_throw_exceptions(['NotAnException']);

        Mockery::close();
    }

    public function testAndThrowsIsAnAliasToAndThrow(): void
    {
        $this->mock->should_receive('foo')
            ->and_throws(new OutOfBoundsException());

        $this->expectException(OutOfBoundsException::class);
        $this->mock->foo();
    }

    public function testAndYield(): void
    {
        $this->mock->should_receive('foo')
            ->and_yield(1, 2, 3);
        self::assertSame([1, 2, 3], \iterator_to_array($this->mock->foo()));
    }

    /**
     * Argument Constraint Tests
     */
    public function testAnyConstraintMatchesAnyArg(): void
    {
        $this->mock->should_receive('foo')
            ->with(1, Mockery::any())->twice();
        $this->mock->foo(1, 2);
        $this->mock->foo(1, 'str');
    }

    public function testAnyConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::any())->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testAnyOfConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::any_of(1, 2))->twice();
        $this->mock->foo(2);
        $this->mock->foo(1);
    }

    public function testAnyOfConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::any_of(1, 2))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testAnyOfConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::any_of(1, 2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(3);
        Mockery::close();
    }

    public function testAnyOfConstraintThrowsExceptionWhenFalseIsNotAnExpectedArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::any_of(0, 1, 2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(false);
    }

    public function testAnyOfConstraintThrowsExceptionWhenTrueIsNotAnExpectedArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::any_of(1, 2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(true);
    }

    public function testArrayConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('array'))->once();
        $this->mock->foo([]);
    }

    public function testArrayConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('array'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testArrayConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('array'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testArrayContentConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::subset([
                'a' => 1,
                'b' => 2,
            ]))->once();
        $this->mock->foo([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
    }

    public function testArrayContentConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::subset([
                'a' => 1,
                'b' => 2,
            ]))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testArrayContentConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::subset([
                'a' => 1,
                'b' => 2,
            ]));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo([
            'a' => 1,
            'c' => 3,
        ]);
        Mockery::close();
    }

    public function testBoolConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('bool'))->once();
        $this->mock->foo(true);
    }

    public function testBoolConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('bool'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testBoolConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('bool'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testByDefaultOnAMockDoesSquatWithoutExpectations(): void
    {
        self::assertInstanceOf(Mock_Interface::class, \mock('f')->by_default());
    }

    public function testByDefaultOperatesFromMockConstruction(): void
    {
        $container = new Container(Mockery::get_default_generator(), Mockery::get_default_loader());
        $mock = $container->mock('f', [
            'foo' => 'rfoo',
            'bar' => 'rbar',
            'baz' => 'rbaz',
        ])->by_default();
        $mock->should_receive('foo')
            ->and_return('foobar');
        self::assertSame('foobar', $mock->foo());
        self::assertSame('rbar', $mock->bar());
        self::assertSame('rbaz', $mock->baz());
    }

    public function testByDefaultPreventedFromSettingDefaultWhenDefaultingExpectationWasReplaced(): void
    {
        $exp = $this->mock->should_receive('foo')
            ->and_return(1);
        $this->mock->should_receive('foo')
            ->and_return(2);
        $this->expectException(\Mockery\Exception::class);
        $exp->by_default();
        Mockery::close();
    }

    public function testCallCountingOnlyAppliesToMatchedExpectations(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->once();
        $this->mock->should_receive('foo')
            ->with(2)
            ->twice();
        $this->mock->should_receive('foo')
            ->with(3);
        $this->mock->foo(1);
        $this->mock->foo(2);
        $this->mock->foo(2);
        $this->mock->foo(3);
    }

    public function testCallCountingThrowsExceptionFirst(): void
    {
        $number_of_calls = 0;
        $this->mock->should_receive('foo')
            ->times(2)
            ->with(Mockery::on(function ($argument) use (&$number_of_calls) {
                $number_of_calls++;
                return $number_of_calls <= 3;
            }));

        $this->mock->foo(1);
        $this->mock->foo(1);
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testCallCountingThrowsExceptionOnAnyMismatch(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->once();
        $this->mock->should_receive('foo')
            ->with(2)
            ->twice();
        $this->mock->should_receive('foo')
            ->with(3);
        $this->mock->should_receive('bar');
        $this->mock->foo(1);
        $this->mock->foo(2);
        $this->mock->foo(3);
        $this->mock->bar();
        $this->expectException(Count_Validator_Exception::class);
        Mockery::close();
    }

    public function testCallableConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('callable'))->once();
        $this->mock->foo(function () {
            return 'f';
        });
    }

    public function testCallableConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('callable'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testCallableConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('callable'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testCalledAtLeastOnceAtExactlyOneCall(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once();
        $this->mock->foo();
    }

    public function testCalledAtLeastOnceAtExactlyThreeCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->times(3);
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testCalledAtLeastThrowsExceptionOnTooFewCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->twice();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        Mockery::close();
    }

    public function testCalledAtLeastThrowsExceptionOnTooManyCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_most()
            ->twice();
        $this->mock->foo();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testCalledAtMostAtExactlyThreeCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_most()
            ->times(3);
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testCalledAtMostOnceAtExactlyOneCall(): void
    {
        $this->mock->should_receive('foo')
            ->at_most()
            ->once();
        $this->mock->foo();
    }

    public function testCalledOnce(): void
    {
        $this->mock->should_receive('foo')
            ->once();
        $this->mock->foo();
    }

    public function testCalledOnceThrowsExceptionIfCalledThreeTimes(): void
    {
        $this->mock->should_receive('foo')
            ->twice();
        $this->mock->foo();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testCalledOnceThrowsExceptionIfCalledTwice(): void
    {
        $this->mock->should_receive('foo')
            ->once();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testCalledOnceThrowsExceptionIfNotCalled(): void
    {
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->should_receive('foo')
            ->once();
        Mockery::close();
    }

    public function testCalledTwice(): void
    {
        $this->mock->should_receive('foo')
            ->twice();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testCalledTwiceThrowsExceptionIfNotCalled(): void
    {
        $this->mock->should_receive('foo')
            ->twice();
        $this->expectException(Count_Validator_Exception::class);
        Mockery::close();
    }

    public function testCalledZeroOrMoreTimesAtThreeCalls(): void
    {
        $this->mock->should_receive('foo')
            ->zero_or_more_times();
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testCalledZeroOrMoreTimesAtZeroCalls(): void
    {
        $this->mock->should_receive('foo')
            ->zero_or_more_times();
    }

    public function testCanReturnSelf(): void
    {
        $this->mock->should_receive('foo')
            ->and_return_self();
        self::assertSame($this->mock, $this->mock->foo());
    }

    public function testCaptureStoresArgumentOfTypeArgumentClosureEvaluatesToTrue(): void
    {
        $object = new stdClass();
        $temp = null;
        $this->mock->should_receive('foo')
            ->with(Mockery::capture($temp))->once();
        $this->mock->foo($object);

        self::assertSame($object, $temp);
    }

    public function testCaptureStoresArgumentOfTypeScalarClosureEvaluatesToTrue(): void
    {
        $temp = null;
        $this->mock->should_receive('foo')
            ->with(Mockery::capture($temp))->once();
        $this->mock->foo(4);

        self::assertSame(4, $temp);
    }

    public function testClassConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('stdClass'))->once();
        $this->mock->foo(new stdClass());
    }

    public function testClassConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('stdClass'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testClassConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('stdClass'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(new \Exception());
        Mockery::close();
    }

    public function testComboOfLeastAndMostCallsThrowsExceptionAtTooFewCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once()
            ->at_most()
            ->twice();
        $this->expectException(Count_Validator_Exception::class);
        Mockery::close();
    }

    public function testComboOfLeastAndMostCallsThrowsExceptionAtTooManyCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once()
            ->at_most()
            ->twice();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        $this->mock->foo();
        Mockery::close();
    }

    public function testComboOfLeastAndMostCallsWithOneCall(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once()
            ->at_most()
            ->twice();
        $this->mock->foo();
    }

    public function testComboOfLeastAndMostCallsWithTwoCalls(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once()
            ->at_most()
            ->twice();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testContainsConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::contains(1, 2))->once();
        $this->mock->foo([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
    }

    public function testContainsConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::contains(1, 2))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testContainsConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::contains(1, 2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo([
            'a' => 1,
            'c' => 3,
        ]);
        Mockery::close();
    }

    public function testCountWithBecauseExceptionMessage(): void
    {
        $this->expectException(Invalid_Count_Exception::class);
        $this->expectExceptionMessageMatches(
            '/Method foo\(<Any Arguments>\) from Mockery_(.*?) should be called' . PHP_EOL . ' ' .
            'exactly 1 times but called 0 times. Because We like foo/'
        );

        $this->mock->should_receive('foo')
            ->once()
            ->because('We like foo');
        Mockery::close();
    }

    public function testDefaultExpectationsAreReplacedByLaterConcreteExpectations(): void
    {
        $this->mock->should_receive('foo')
            ->and_return('bar')
            ->once()
            ->by_default();
        $this->mock->should_receive('foo')
            ->and_return('baz')
            ->twice();
        self::assertSame('baz', $this->mock->foo());
        self::assertSame('baz', $this->mock->foo());
    }

    public function testDefaultExpectationsCanBeOrdered(): void
    {
        $this->mock->should_receive('foo')
            ->ordered()
            ->by_default();
        $this->mock->should_receive('bar')
            ->ordered()
            ->by_default();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->bar();
        $this->mock->foo();
        Mockery::close();
    }

    public function testDefaultExpectationsCanBeOrderedAndReplaced(): void
    {
        $this->mock->should_receive('foo')
            ->ordered()
            ->by_default();
        $this->mock->should_receive('bar')
            ->ordered()
            ->by_default();
        $this->mock->should_receive('bar')
            ->ordered()
            ->once();
        $this->mock->should_receive('foo')
            ->ordered()
            ->once();
        $this->mock->bar();
        $this->mock->foo();
    }

    public function testDefaultExpectationsCanBeOverridden(): void
    {
        $this->mock->should_receive('foo')
            ->with('test')
            ->and_return('bar')
            ->by_default();
        $this->mock->should_receive('foo')
            ->with('test')
            ->and_return('newbar')
            ->by_default();
        $this->mock->foo('test');
        self::assertSame('newbar', $this->mock->foo('test'));
    }

    public function testDefaultExpectationsValidatedInCorrectOrder(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->once()
            ->and_return('first')
            ->by_default();
        $this->mock->should_receive('foo')
            ->with(2)
            ->once()
            ->and_return('second')
            ->by_default();
        self::assertSame('first', $this->mock->foo(1));
        self::assertSame('second', $this->mock->foo(2));
    }

    public function testDifferentArgumentsAndOrderingsPassWithoutException(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->ordered()
            ->once();
        $this->mock->should_receive('foo')
            ->with(2)
            ->ordered()
            ->once();
        $this->mock->foo(1);
        $this->mock->foo(2);
    }

    public function testDifferentArgumentsAndOrderingsThrowExceptionWhenInWrongOrder(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->ordered();
        $this->mock->should_receive('foo')
            ->with(2)
            ->ordered();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(2);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testDoubleConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('double'))->once();
        $this->mock->foo(2.25);
    }

    public function testDoubleConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('double'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testDoubleConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('double'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testDucktypeConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::ducktype('quack', 'swim'))->once();
        $this->mock->foo(new Mockery_Duck());
    }

    public function testDucktypeConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::ducktype('quack', 'swim'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testDucktypeConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::ducktype('quack', 'swim'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(new Mockery_Duck_Nonswimmer());
        Mockery::close();
    }

    public function testEnsuresOrderingIsCrossMockWhenGloballyFlagSet(): void
    {
        $this->mock->should_receive('foo')
            ->globally()
            ->ordered();
        $mock2 = \mock('bar');
        $mock2->should_receive('bar')
            ->globally()
            ->ordered();
        $this->expectException(\Mockery\Exception::class);
        $mock2->bar();
        $this->mock->foo();
        Mockery::close();
    }

    public function testEnsuresOrderingIsNotCrossMockByDefault(): void
    {
        $this->mock->should_receive('foo')
            ->ordered()
            ->once();
        $mock2 = \mock('bar');
        $mock2->should_receive('bar')
            ->ordered()
            ->once();
        $mock2->bar();
        $this->mock->foo();
    }

    public function testExactCountersOverrideAnyPriorSetNonExactCounters(): void
    {
        $this->mock->should_receive('foo')
            ->at_least()
            ->once()
            ->once();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testExceptionOnArgumentIndexOutOfRange(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->mock->should_receive('foo')
            ->and_return_arg(2);
        $this->mock->foo(0, 1); // only pass 2 arguments so index #2 won't exist
    }

    public function testExceptionOnInvalidArgumentIndexValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mock->should_receive('foo')
            ->and_return_arg('invalid');
    }

    public function testExpectationCanBeOverridden(): void
    {
        $this->mock->should_receive('foo')
            ->once()
            ->and_return('green');
        $this->mock->should_receive('foo')
            ->and_return('blue');
        self::assertSame('green', $this->mock->foo());
        self::assertSame('blue', $this->mock->foo());
    }

    public function testExpectationCastToStringFormatting(): void
    {
        $exp = $this->mock->should_receive('foo')
            ->with(1, 'bar', new stdClass(), [
                'Spam' => 'Ham',
                'Bar' => 'Baz',
            ]);
        self::assertSame("[foo(1, 'bar', object(stdClass), ['Spam' => 'Ham', 'Bar' => 'Baz'])]", (string) $exp);
    }

    public function testExpectationFallsBackToDefaultExpectationWhenConcreteExpectationsAreUsedUp(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->and_return('bar')
            ->once()
            ->by_default();
        $this->mock->should_receive('foo')
            ->with(2)
            ->and_return('baz')
            ->once();
        self::assertSame('baz', $this->mock->foo(2));
        self::assertSame('bar', $this->mock->foo(1));
    }

    public function testExpectationMatchingWithAnyArgsOrderings(): void
    {
        $this->mock->should_receive('foo')
            ->with_any_args()
            ->once()
            ->ordered();
        $this->mock->should_receive('bar')
            ->with_any_args()
            ->once()
            ->ordered();
        $this->mock->should_receive('foo')
            ->with_any_args()
            ->once()
            ->ordered();
        $this->mock->foo();
        $this->mock->bar();
        $this->mock->foo();
    }

    public function testExpectationMatchingWithNoArgsOrderings(): void
    {
        $this->mock->should_receive('foo')
            ->with_no_args()
            ->once()
            ->ordered();
        $this->mock->should_receive('bar')
            ->with_no_args()
            ->once()
            ->ordered();
        $this->mock->should_receive('foo')
            ->with_no_args()
            ->once()
            ->ordered();
        $this->mock->foo();
        $this->mock->bar();
        $this->mock->foo();
    }

    public function testExpectationsCanBeMarkedAsDefaults(): void
    {
        $this->mock->should_receive('foo')
            ->and_return('bar')
            ->by_default();
        self::assertSame('bar', $this->mock->foo());
    }

    public function testExpectsAnyArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with_any_args()
            ->times(3);
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 'k', new stdClass());
    }

    public function testExpectsAndOthers(): void
    {
        $this->mock->should_receive('foo')
            ->with_and_others(1, 2)
            ->times(2);
        $this->mock->foo(1, 2);
        $this->mock->foo(1, 2, new stdClass(), 'a', 'b', 'c');
    }

    public function testExpectsArgumentMatchingObjectType(): void
    {
        $this->mock->should_receive('foo')
            ->with(stdClass::class)->once();
        $this->mock->foo(new stdClass());
    }

    public function testExpectsArgumentsArray(): void
    {
        $this->mock->should_receive('foo')
            ->with_args([1, 2])->once();
        $this->mock->foo(1, 2);
    }

    public function testExpectsArgumentsArrayAcceptAClosureThatValidatesPassedArguments(): void
    {
        $closure = function ($odd, $even) {
            return ($odd % 2 !== 0) && ($even % 2 === 0);
        };
        $this->mock->should_receive('foo')
            ->with_args($closure)
            ->once();
        $this->mock->foo(1, 2);
    }

    public function testExpectsArgumentsArrayClosureDoesNotThrowExceptionIfOptionalArgumentsAreMissing(): void
    {
        $closure = function ($odd, $even, $sum = null) {
            $result = ($odd % 2 !== 0) && ($even % 2 === 0);
            if ($sum !== null) {
                return $result && ($odd + $even === $sum);
            }
            return $result;
        };
        $this->mock->should_receive('foo')
            ->with_args($closure)
            ->once();
        $this->mock->foo(1, 4);
    }

    public function testExpectsArgumentsArrayClosureDoesNotThrowExceptionIfOptionalArgumentsMathTheExpectation(): void
    {
        $closure = function ($odd, $even, $sum = null) {
            $result = ($odd % 2 !== 0) && ($even % 2 === 0);
            if ($sum !== null) {
                return $result && ($odd + $even === $sum);
            }
            return $result;
        };
        $this->mock->should_receive('foo')
            ->with_args($closure)
            ->once();
        $this->mock->foo(1, 4, 5);
    }

    public function testExpectsArgumentsArrayClosureThrowsExceptionIfOptionalArgumentsDontMatchTheExpectation(): void
    {
        $closure = function ($odd, $even, $sum = null) {
            $result = ($odd % 2 !== 0) && ($even % 2 === 0);
            if ($sum !== null) {
                return $result && ($odd + $even === $sum);
            }
            return $result;
        };
        $this->mock->should_receive('foo')
            ->with_args($closure);
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1, 4, 2);
        Mockery::close();
    }

    public function testExpectsArgumentsArrayThrowsExceptionIfNoArgumentsPassed(): void
    {
        $this->mock->should_receive('foo')
            ->with();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testExpectsArgumentsArrayThrowsExceptionIfPassedEmptyArray(): void
    {
        $this->mock->should_receive('foo')
            ->with_args([]);
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1, 2);
        Mockery::close();
    }

    public function testExpectsArgumentsArrayThrowsExceptionIfPassedWrongArgumentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/invalid argument (.+), only array and closure are allowed/');
        $this->mock->should_receive('foo')
            ->with_args(5);
        Mockery::close();
    }

    public function testExpectsArgumentsArrayThrowsExceptionIfPassedWrongArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with_args([1, 2]);
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(3, 4);
        Mockery::close();
    }

    public function testExpectsArgumentsArrayThrowsExceptionWhenClosureEvaluatesToFalse(): void
    {
        $closure = function ($odd, $even) {
            return ($odd % 2 !== 0) && ($even % 2 === 0);
        };
        $this->mock->should_receive('foo')
            ->with_args($closure);
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(4, 2);
        Mockery::close();
    }

    public function testExpectsNoArgumentCalledAtLeastOnceOverridingDefaultOnceCall(): void
    {
        $this->mock->expects()
            ->foo()
            ->at_least()
            ->once();
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testExpectsNoArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with_no_args()
            ->once();
        $this->mock->foo();
    }

    public function testExpectsNoArgumentsThrowsExceptionIfAnyPassed(): void
    {
        $this->mock->should_receive('foo')
            ->with_no_args();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testExpectsSomeOfArgumentsGivenArgsDoNotMatchRealArgsAndThrowNoMatchingException(): void
    {
        $this->mock->should_receive('foo')
            ->with_some_of_args(1, 3, 5);
        $this->expectException(No_Matching_Expectation_Exception::class);
        $this->mock->foo(1, 2, 4, 5);
    }

    public function testExpectsSomeOfArgumentsMatchRealArguments(): void
    {
        $this->mock->should_receive('foo')
            ->with_some_of_args(1, 3, 5)
            ->times(4);
        $this->mock->foo(1, 2, 3, 4, 5);
        $this->mock->foo(1, 3, 5, 2, 4);
        $this->mock->foo(1, 'foo', 3, 'bar', 5);
        $this->mock->foo(1, 3, 5);
        $this->mock->should_receive('foo')
            ->with_some_of_args('foo')
            ->times(2);
        $this->mock->foo('foo', 'bar');
        $this->mock->foo('bar', 'foo');
    }

    public function testExpectsStringArgumentCalledAtLeastOnceOverridingDefaultOnceCall(): void
    {
        $this->mock->expects('foo')
            ->at_least()
            ->once();
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testExpectsStringArgumentExceptionMessageDifferentiatesBetweenNullAndEmptyString(): void
    {
        $this->mock->should_receive('foo')
            ->with_args(['a string']);
        $this->expectException(\Mockery\Exception::class);
        $this->expectExceptionMessageMatches('/foo\(NULL\)/');
        $this->mock->foo(null);
        Mockery::close();
    }

    public function testFloatConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('float'))->once();
        $this->mock->foo(2.25);
    }

    public function testFloatConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('float'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testFloatConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('float'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testGlobalConfigMayForbidMockingNonExistentMethodsOnAutoDeclaredClasses(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $this->expectException(\Mockery\Exception::class);
        $this->expectExceptionMessage("Mockery can't find 'SomeMadeUpClass' so can't mock it");
        $mock = \mock('SomeMadeUpClass');
        $mock->should_receive('foo');
        Mockery::close();
    }

    public function testGlobalConfigMayForbidMockingNonExistentMethodsOnClasses(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(stdClass::class);
        $this->expectException(\Mockery\Exception::class);
        $mock->should_receive('foo');
        Mockery::close();
    }

    public function testGlobalConfigMayForbidMockingNonExistentMethodsOnObjects(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $mock = \mock(new stdClass());
        $this->expectException(\Mockery\Exception::class);
        $mock->should_receive('foo');
        Mockery::close();
    }

    public function testGlobalConfigQuickDefinitionsConfigurationDefaultExpectation(): void
    {
        Mockery::get_configuration()->get_quick_definitions()->should_be_called_at_least_once(false);
        \mock([
            'foo' => 1,
        ]);
        $this->expectNotToPerformAssertions();
        Mockery::close();
    }

    public function testGlobalConfigQuickDefinitionsConfigurationMockAtLeastOnce(): void
    {
        Mockery::get_configuration()->get_quick_definitions()->should_be_called_at_least_once(true);
        \mock([
            'foo' => 1,
        ]);
        $this->expectException(Invalid_Count_Exception::class);
        Mockery::close();
    }

    public function testGroupedOrderingThrowsExceptionWhenCallsDisordered(): void
    {
        $this->mock->should_receive('foo')
            ->ordered('first');
        $this->mock->should_receive('bar')
            ->ordered('second');
        $this->expectException(\Mockery\Exception::class);
        $this->mock->bar();
        $this->mock->foo();
        Mockery::close();
    }

    public function testGroupedOrderingWithLimitsAllowsMultipleReturnValues(): void
    {
        $this->mock->should_receive('foo')
            ->with(2)
            ->once()
            ->and_return('first');
        $this->mock->should_receive('foo')
            ->with(2)
            ->twice()
            ->and_return('second/third');
        $this->mock->should_receive('foo')
            ->with(2)
            ->and_return('infinity');
        self::assertSame('first', $this->mock->foo(2));
        self::assertSame('second/third', $this->mock->foo(2));
        self::assertSame('second/third', $this->mock->foo(2));
        self::assertSame('infinity', $this->mock->foo(2));
        self::assertSame('infinity', $this->mock->foo(2));
        self::assertSame('infinity', $this->mock->foo(2));
    }

    public function testGroupedUngroupedOrderingDoNotOverlap(): void
    {
        $s = $this->mock->should_receive('start')
            ->ordered();
        $m = $this->mock->should_receive('mid')
            ->ordered('foobar');
        $e = $this->mock->should_receive('end')
            ->ordered();
        self::assertLessThan($m->get_order_number(), $s->get_order_number());
        self::assertLessThan($e->get_order_number(), $m->get_order_number());
    }

    public function testHasKeyConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::has_key('c'))->once();
        $this->mock->foo([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
    }

    public function testHasKeyConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::has_key('a'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, [
            'a' => 1,
        ], 3);
    }

    public function testHasKeyConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::has_key('c'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo([
            'a' => 1,
            'b' => 3,
        ]);
        Mockery::close();
    }

    public function testHasValueConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::has_value(1))->once();
        $this->mock->foo([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
    }

    public function testHasValueConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::has_value(1))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, [
            'a' => 1,
        ], 3);
    }

    public function testHasValueConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::has_value(2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo([
            'a' => 1,
            'b' => 3,
        ]);
        Mockery::close();
    }

    public function testIfCallingMethodWithNoExpectationsHasSpecificExceptionMessage(): void
    {
        $mock = \mock(Mockery_Duck::class);

        $this->expectException(
            Bad_Method_Call_Exception::class,
            'Received ' . \get_class($mock) .
            '::quack(), ' . 'but no expectations were specified'
        );

        $mock->quack();
        Mockery::close();
    }

    public function testIfExceptionIndicatesAbsenceOfMethodAndExpectationsOnMock(): void
    {
        $mock = \mock(Mockery_Duck::class);

        $this->expectException(
            Bad_Method_Call_Exception::class,
            'Method ' . \get_class($mock) .
            '::nonExistent() does not exist on this mock object'
        );

        $mock->nonExistent();
        Mockery::close();
    }

    public function testIntConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('int'))->once();
        $this->mock->foo(2);
    }

    public function testIntConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('int'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testIntConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('int'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testItCanThrowAThrowable(): void
    {
        $this->expectException(Error::class);
        $this->mock->should_receive('foo')
            ->and_throw(new Error());
        $this->mock->foo();
    }

    public function testItUsesAMatchersToStringMethodInTheExceptionOutput(): void
    {
        $mock = Mockery::mock();

        $mock->expects()
            ->foo(Mockery::has_key('foo'));

        $this->expectException(Invalid_Count_Exception::class);
        $this->expectExceptionMessage('Method foo(<HasKey[foo]>)');

        Mockery::close();
    }

    public function testLongConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('long'))->once();
        $this->mock->foo(2);
    }

    public function testLongConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('long'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testLongConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('long'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testLongExpectationCastToStringFormatting(): void
    {
        $exp = $this->mock->should_receive('foo')
            ->with([
                'Spam' => 'Ham',
                'Bar' => 'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'Bar',
                'Baz',
                'End',
            ]);
        self::assertSame(
            "[foo(['Spam' => 'Ham', 'Bar' => 'Baz', 0 => 'Bar', 1 => 'Baz', 2 => 'Bar', 3 => 'Baz', 4 => 'Bar', 5 => 'Baz', 6 => 'Bar', 7 => 'Baz', 8 => 'Bar', 9 => 'Baz', 10 => 'Bar', 11 => 'Baz', 12 => 'Bar', 13 => 'Baz', 14 => 'Bar', 15 => 'Baz', 16 => 'Bar', 17 => 'Baz', 18 => 'Bar', 19 => 'Baz', 20 => 'Bar', 21 => 'Baz', 22 => 'Bar', 23 => 'Baz', 24 => 'Bar', 25 => 'Baz', 26 => 'Bar', 27 => 'Baz', 28 => 'Bar', 29 => 'Baz', 30 => 'Bar', 31 => 'Baz', 32 => 'Bar', 33 => 'Baz', 34 => 'Bar', 35 => 'Baz', 36 => 'Bar', 37 => 'Baz', 38 => 'Bar', 39 => 'Baz', 40 => 'Bar', 41 => 'Baz', 42 => 'Bar', 43 => 'Baz', 44 => 'Bar', 45 => 'Baz', 46 => 'Baz', 47 => 'Bar', 48 => 'Baz', 49 => 'Bar', 50 => 'Baz', 51 => 'Bar', 52 => 'Baz', 53 => 'Bar', 54 => 'Baz', 55 => 'Bar', 56 => 'Baz', 57 => 'Baz', 58 => 'Bar', 59 => 'Baz', 60 => 'Bar', 61 => 'Baz', 62 => 'Bar', 63 => 'Baz', 64 => 'Bar', 65 => 'Baz', 66 => 'Bar', 67 => 'Baz', 68 => 'Baz', 69 => 'Bar', 70 => 'Baz', 71 => 'Bar', 72 => 'Baz', 73 => 'Bar', 74 => 'Baz', 7...])]",
            (string) $exp
        );
    }

    public function testMakePartialExpectationBasedOnArgs(): void
    {
        $mock = \mock(MockeryTest_SubjectCall1::class)->make_partial();

        self::assertSame('bar', $mock->foo());
        self::assertSame('bar', $mock->foo('baz'));
        self::assertSame('bar', $mock->foo('qux'));

        $mock->should_receive('foo')
            ->with('baz')
            ->twice()
            ->and_return('123');
        self::assertSame('bar', $mock->foo());
        self::assertSame('123', $mock->foo('baz'));
        self::assertSame('bar', $mock->foo('qux'));

        $mock->should_receive('foo')
            ->with_no_args()
            ->once()
            ->and_return('456');
        self::assertSame('456', $mock->foo());
        self::assertSame('123', $mock->foo('baz'));
        self::assertSame('bar', $mock->foo('qux'));
    }

    public function testMatchPrecedenceBasedOnExpectedCallsFavouringAnyMatch(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::any())->once();
        $this->mock->should_receive('foo')
            ->with(1)
            ->never();
        $this->mock->foo(1);
    }

    public function testMatchPrecedenceBasedOnExpectedCallsFavouringExplicitMatch(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->once();
        $this->mock->should_receive('foo')
            ->with(Mockery::any())->never();
        $this->mock->foo(1);
    }

    public function testMockShouldNotBeAnonymousWhenImplementingSpecificInterface(): void
    {
        $waterMock = \mock(IWater::class);
        self::assertFalse($waterMock->mockery_is_anonymous());
    }

    public function testMockedMethodsCallableFromWithinOriginalClass(): void
    {
        $mock = \mock(MockeryTest_InterMethod1::class . '[doThird]');
        $mock->should_receive('doThird')
            ->and_return(true);
        self::assertTrue($mock->doFirst());
    }

    public function testMockingDemeterChainsPassesMockeryExpectationToCompositeExpectation(): void
    {
        $mock = \mock(Mockery_Demeterowski::class);
        $mock->should_receive('foo->bar->baz')
            ->and_return('Spam!');
        $demeter = new Mockery_UseDemeter($mock);
        self::assertSame('Spam!', $demeter->doit());
    }

    public function testMockingDemeterChainsPassesMockeryExpectationToCompositeExpectationWithArgs(): void
    {
        $mock = \mock(Mockery_Demeterowski::class);
        $mock->should_receive('foo->bar->baz')
            ->and_return('Spam!');
        $demeter = new Mockery_UseDemeter($mock);
        self::assertSame('Spam!', $demeter->doitWithArgs());
    }

    public function testMultipleExpectationCastToStringFormatting(): void
    {
        $exp = $this->mock->should_receive('foo', 'bar')
            ->with(1);
        self::assertSame('[foo(1), bar(1)]', (string) $exp);
    }

    public function testMultipleExpectationsWithReturns(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->and_return(10);
        $this->mock->should_receive('bar')
            ->with(2)
            ->and_return(20);
        self::assertSame(10, $this->mock->foo(1));
        self::assertSame(20, $this->mock->bar(2));
    }

    public function testMustBeConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::must_be(2))->once();
        $this->mock->foo(2);
    }

    public function testMustBeConstraintMatchesObjectArgumentWithEqualsComparisonNotIdentical(): void
    {
        $a = new stdClass();
        $a->foo = 1;
        $b = new stdClass();
        $b->foo = 1;
        $this->mock->should_receive('foo')
            ->with(Mockery::must_be($a))->once();
        $this->mock->foo($b);
    }

    public function testMustBeConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::must_be(2))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testMustBeConstraintNonMatchingCaseWithObject(): void
    {
        $a = new stdClass();
        $a->foo = 1;
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::must_be($a))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, $a, 3);
    }

    public function testMustBeConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::must_be(2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('2');
        Mockery::close();
    }

    public function testMustBeConstraintThrowsExceptionWhenConstraintUnmatchedWithObject(): void
    {
        $a = new stdClass();
        $a->foo = 1;
        $b = new stdClass();
        $b->foo = 2;
        $this->mock->should_receive('foo')
            ->with(Mockery::must_be($a));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo($b);
        Mockery::close();
    }

    public function testNeverCalled(): void
    {
        $this->mock->should_receive('foo')
            ->never();
    }

    public function testNeverCalledThrowsExceptionOnCall(): void
    {
        $this->mock->should_receive('foo')
            ->never();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testNonObjectEqualsExpectation(): void
    {
        $input = [
            'club_id' => 1,
            'user_id' => 1,
            'is_admin' => 1,
        ];

        $foo = Mockery::mock();

        $foo->should_receive('foo')
            ->with($input)
            ->and_return('foobar');

        // only sort the input to change the order but not the values.
        \ksort($input);

        self::assertSame('foobar', $foo->foo($input));
    }

    public function testNotAnyOfConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::not_any_of(1, 2))->once();
        $this->mock->foo(3);
    }

    public function testNotAnyOfConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::not_any_of(1, 2))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 4, 3);
    }

    public function testNotAnyOfConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::not_any_of(1, 2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(2);
        Mockery::close();
    }

    public function testNotConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::not(1))->once();
        $this->mock->foo(2);
    }

    public function testNotConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::not(2))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testNotConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::not(2));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(2);
        Mockery::close();
    }

    public function testNullConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('null'))->once();
        $this->mock->foo(null);
    }

    public function testNullConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('null'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testNullConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('null'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testNumericConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('numeric'))->once();
        $this->mock->foo('2');
    }

    public function testNumericConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('numeric'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testNumericConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('numeric'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testObjectConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('object'))->once();
        $this->mock->foo(new stdClass());
    }

    public function testObjectConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('object`'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testObjectConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('object'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testOnConstraintMatchesArgumentClosureEvaluatesToTrue(): void
    {
        $function = function ($arg) {
            return $arg % 2 === 0;
        };
        $this->mock->should_receive('foo')
            ->with(Mockery::on($function))->once();
        $this->mock->foo(4);
    }

    public function testOnConstraintMatchesArgumentOfTypeArrayClosureEvaluatesToTrue(): void
    {
        $function = function ($arg) {
            return \is_array($arg);
        };
        $this->mock->should_receive('foo')
            ->with(Mockery::on($function))->once();
        $this->mock->foo([4, 5]);
    }

    public function testOnConstraintThrowsExceptionWhenConstraintUnmatchedClosureEvaluatesToFalse(): void
    {
        $function = function ($arg) {
            return $arg % 2 === 0;
        };
        $this->mock->should_receive('foo')
            ->with(Mockery::on($function));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(5);
        Mockery::close();
    }

    public function testOptionalMockRetrieval(): void
    {
        $m = \mock('f')
            ->should_receive('foo')
            ->with(1)
            ->and_return(3)
            ->mock();
        self::assertInstanceOf(Mock_Interface::class, $m);
    }

    public function testOrderedCallsWithOutOfOrderError(): void
    {
        $this->mock->should_receive('foo')
            ->ordered();
        $this->mock->should_receive('bar')
            ->ordered();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->bar();
        $this->mock->foo();
        Mockery::close();
    }

    public function testOrderedCallsWithoutError(): void
    {
        $this->mock->should_receive('foo')
            ->ordered()
            ->once();
        $this->mock->should_receive('bar')
            ->ordered()
            ->once();
        $this->mock->foo();
        $this->mock->bar();
    }

    public function testOrderingOfDefaultGrouping(): void
    {
        $this->mock->should_receive('foo')
            ->ordered()
            ->once();
        $this->mock->should_receive('bar')
            ->ordered()
            ->once();
        $this->mock->foo();
        $this->mock->bar();
    }

    public function testOrderingOfDefaultGroupingThrowsExceptionOnWrongOrder(): void
    {
        $this->mock->should_receive('foo')
            ->ordered();
        $this->mock->should_receive('bar')
            ->ordered();
        $this->expectException(\Mockery\Exception::class);
        $this->mock->bar();
        $this->mock->foo();
        Mockery::close();
    }

    public function testOrderingUsingNamedGroups(): void
    {
        $this->mock->should_receive('start')
            ->ordered('start')
            ->once();
        $this->mock->should_receive('foo')
            ->ordered('foobar')
            ->once();
        $this->mock->should_receive('bar')
            ->ordered('foobar')
            ->twice();
        $this->mock->should_receive('final')
            ->ordered()
            ->once();
        $this->mock->start();
        $this->mock->bar();
        $this->mock->foo();
        $this->mock->bar();
        $this->mock->final();
    }

    public function testOrderingUsingNumberedGroups(): void
    {
        $this->mock->should_receive('start')
            ->ordered(1)
            ->once();
        $this->mock->should_receive('foo')
            ->ordered(2)
            ->once();
        $this->mock->should_receive('bar')
            ->ordered(2)
            ->twice();
        $this->mock->should_receive('final')
            ->ordered()
            ->once();
        $this->mock->start();
        $this->mock->bar();
        $this->mock->foo();
        $this->mock->bar();
        $this->mock->final();
    }

    public function testPassthruCallMagic(): void
    {
        $mock = \mock(Mockery_Magic::class);
        $mock->should_receive('theAnswer')
            ->once()
            ->passthru();
        self::assertSame(42, $mock->theAnswer());
    }

    public function testPassthruEnsuresRealMethodCalledForReturnValues(): void
    {
        $mock = \mock(MockeryTest_SubjectCall1::class);
        $mock->should_receive('foo')
            ->once()
            ->passthru();
        self::assertSame('bar', $mock->foo());
    }

    public function testPatternConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::pattern('/foo.*/'))->once();
        $this->mock->foo('foobar');
    }

    public function testPatternConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->once();
        $this->mock->should_receive('foo')
            ->with(Mockery::pattern('/foo.*/'))->never();
        $this->mock->foo('bar');
    }

    public function testPatternConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::pattern('/foo.*/'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('bar');
        Mockery::close();
    }

    public function testRealConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('float'))->once();
        $this->mock->foo(2.25);
    }

    public function testRealConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('float'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testRealConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('float'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testResourceConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('resource'))->once();
        $r = \fopen(\dirname(__FILE__, 3) . '/Fixture/_files/file.txt', 'rb');
        $this->mock->foo($r);
    }

    public function testResourceConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('resource'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testResourceConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('resource'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo('f');
        Mockery::close();
    }

    public function testReturnAsUndefinedAllowsForInfiniteSelfReturningChain(): void
    {
        $this->mock->should_ignore_missing()
            ->as_undefined();
        self::assertInstanceOf(Undefined::class, $this->mock->g(1, 2)->a()->b()->c());
    }

    public function testReturnNullIfIgnoreMissingMethodsSet(): void
    {
        $this->mock->should_ignore_missing();
        self::assertNull($this->mock->g(1, 2));
    }

    public function testReturnUndefinedIfIgnoreMissingMethodsSet(): void
    {
        $this->mock->should_ignore_missing()
            ->as_undefined();
        self::assertInstanceOf(Undefined::class, $this->mock->g(1, 2));
    }

    public function testReturnsFalseIfFalseIsReturnValue(): void
    {
        $this->mock->should_receive('foo')
            ->and_return_false();
        self::assertFalse($this->mock->foo());
    }

    public function testReturnsNullArgument(): void
    {
        $args = [1, null, 3];
        $index = 1;
        $this->mock->should_receive('foo')
            ->with_args($args)
            ->and_return_arg($index);
        self::assertNull($this->mock->foo(...$args));
    }

    public function testReturnsNullForMockedExistingClassIfAndReturnNullCalled(): void
    {
        $mock = \mock(MockeryTest_Foo::class);
        $mock->should_receive('foo')
            ->and_return(null);
        self::assertNull($mock->foo());
    }

    public function testReturnsNullForMockedExistingClassIfNullIsReturnValue(): void
    {
        $mock = \mock(MockeryTest_Foo::class);
        $mock->should_receive('foo')
            ->and_return_null();
        self::assertNull($mock->foo());
    }

    public function testReturnsNullIfNullIsReturnValue(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(null);
        self::assertNull($this->mock->foo());
    }

    public function testReturnsNullWhenManyArgs(): void
    {
        $this->mock->should_receive('foo');
        self::assertNull($this->mock->foo('foo', [], new stdClass()));
    }

    public function testReturnsNullWhenNoArgs(): void
    {
        $this->mock->should_receive('foo');
        self::assertNull($this->mock->foo());
    }

    public function testReturnsNullWhenSingleArg(): void
    {
        $this->mock->should_receive('foo');
        self::assertNull($this->mock->foo(1));
    }

    public function testReturnsSameValueForAllIfNoArgsExpectationAndNoneGiven(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(1);
        self::assertSame(1, $this->mock->foo());
    }

    public function testReturnsSameValueForAllIfNoArgsExpectationAndSomeGiven(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(1);
        self::assertSame(1, $this->mock->foo('foo'));
    }

    public function testReturnsTrueIfTrueIsReturnValue(): void
    {
        $this->mock->should_receive('foo')
            ->and_return_true();
        self::assertTrue($this->mock->foo());
    }

    public function testReturnsUndefined(): void
    {
        $this->mock->should_receive('foo')
            ->and_return_undefined();
        self::assertInstanceOf(Undefined::class, $this->mock->foo());
    }

    public function testReturnsValueFromSequenceSequentially(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(1, 2, 3);
        $this->mock->foo('foo');
        self::assertSame(2, $this->mock->foo('foo'));
    }

    public function testReturnsValueFromSequenceSequentiallyAndRepeatedlyReturnsFinalValueOnExtraCalls(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(1, 2, 3);
        $this->mock->foo('foo');
        $this->mock->foo('foo');
        self::assertSame(3, $this->mock->foo('foo'));
        self::assertSame(3, $this->mock->foo('foo'));
    }

    public function testReturnsValueFromSequenceSequentiallyAndRepeatedlyReturnsFinalValueOnExtraCallsWithManyAndReturnCalls(): void
    {
        $this->mock->should_receive('foo')
            ->and_return(1)
            ->and_return(2, 3);
        $this->mock->foo('foo');
        $this->mock->foo('foo');
        self::assertSame(3, $this->mock->foo('foo'));
        self::assertSame(3, $this->mock->foo('foo'));
    }

    public function testReturnsValueOfArgument(): void
    {
        $args = [1, 2, 3, 4, 5];
        $index = 2;
        $this->mock->should_receive('foo')
            ->with_args($args)
            ->and_return_arg($index);
        self::assertSame($args[$index], $this->mock->foo(...$args));
    }

    public function testReturnsValueOfClosure(): void
    {
        $this->mock->should_receive('foo')
            ->with(5)
            ->and_return_using(function ($v) {
                return $v + 1;
            });
        self::assertSame(6, $this->mock->foo(5));
    }

    public function testReturnsValuesSetAsArray(): void
    {
        $this->mock->should_receive('foo')
            ->and_return_values([1, 2, 3]);
        self::assertSame(1, $this->mock->foo());
        self::assertSame(2, $this->mock->foo());
        self::assertSame(3, $this->mock->foo());
    }

    public function testScalarConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('scalar'))->once();
        $this->mock->foo(2);
    }

    public function testScalarConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('scalar'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testScalarConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('scalar'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo([]);
        Mockery::close();
    }

    public function testSetsPublicPropertiesCorrectlyForDifferentInstancesOfSameClass(): void
    {
        $mockInstanceOne = \mock(MockeryTest_Foo::class);
        $mockInstanceTwo = \mock(MockeryTest_Foo::class);

        $mockInstanceOne->should_receive('foo')
            ->and_set('bar', 'baz');

        $mockInstanceTwo->should_receive('foo')
            ->and_set('bar', 'bazz');

        $mockInstanceOne->foo();
        $mockInstanceTwo->foo();

        self::assertSame('baz', $mockInstanceOne->bar);
        self::assertSame('bazz', $mockInstanceTwo->bar);
    }

    public function testSetsPublicPropertiesWhenRequested(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->and_set('bar', 'baz', 'bazz', 'bazzz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazzz', $this->mock->bar);
    }

    public function testSetsPublicPropertiesWhenRequestedMoreTimesThanSetValues(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->and_set('bar', 'baz', 'bazz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
    }

    public function testSetsPublicPropertiesWhenRequestedMoreTimesThanSetValuesUsingAlias(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->and_set('bar', 'baz', 'bazz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
    }

    public function testSetsPublicPropertiesWhenRequestedMoreTimesThanSetValuesWithDirectSet(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->and_set('bar', 'baz', 'bazz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->bar = null;
        $this->mock->foo();
        self::assertNull($this->mock->bar);
    }

    public function testSetsPublicPropertiesWhenRequestedMoreTimesThanSetValuesWithDirectSetUsingAlias(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->set('bar', 'baz', 'bazz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->bar = null;
        $this->mock->foo();
        self::assertNull($this->mock->bar);
    }

    public function testSetsPublicPropertiesWhenRequestedUsingAlias(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->set('bar', 'baz', 'bazz', 'bazzz');
        self::assertEmpty($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazz', $this->mock->bar);
        $this->mock->foo();
        self::assertSame('bazzz', $this->mock->bar);
    }

    public function testSetsPublicPropertyWhenRequested(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->and_set('bar', 'baz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
    }

    public function testSetsPublicPropertyWhenRequestedUsingAlias(): void
    {
        $this->mock->bar = null;
        $this->mock->should_receive('foo')
            ->set('bar', 'baz');
        self::assertNull($this->mock->bar);
        $this->mock->foo();
        self::assertSame('baz', $this->mock->bar);
    }

    public function testShouldIgnoreMissingAsDefinedProxiesToUndefinedAllowingToString(): void
    {
        $this->mock->should_ignore_missing()
            ->as_undefined();
        self::assertIsString("{$this->mock->g()}");
        self::assertIsString("{$this->mock}");
    }

    public function testShouldIgnoreMissingAsUndefinedFluentInterface(): void
    {
        self::assertInstanceOf(Mock_Interface::class, $this->mock->should_ignore_missing()->as_undefined());
    }

    public function testShouldIgnoreMissingDefaultReturnValue(): void
    {
        $this->mock->should_ignore_missing(1);
        self::assertSame(1, $this->mock->a());
    }

    /**
     * @issue #253
     */
    public function testShouldIgnoreMissingDefaultSelfAndReturnsSelf(): void
    {
        $this->mock->should_ignore_missing(Mockery::self());
        self::assertSame($this->mock, $this->mock->a()->b());
    }

    public function testShouldIgnoreMissingExpectationBasedOnArgs(): void
    {
        $mock = \mock(MyService2::class)->should_ignore_missing();
        $mock->should_receive('hasBookmarksTagged')
            ->with('dave')
            ->once();
        $mock->hasBookmarksTagged('dave');
        $mock->hasBookmarksTagged('padraic');
    }

    public function testShouldIgnoreMissingFluentInterface(): void
    {
        self::assertInstanceOf(Mock_Interface::class, $this->mock->should_ignore_missing());
    }

    public function testShouldNotReceive(): void
    {
        $this->mock->should_not_receive('foo');
    }

    public function testShouldNotReceiveCanBeAddedToCompositeExpectation(): void
    {
        $mock = \mock('Foo');
        $mock->should_receive('a')
            ->once()
            ->and_return('Spam!')
            ->should_not_receive('b');
        $mock->a();
    }

    public function testShouldNotReceiveThrowsExceptionIfMethodCalled(): void
    {
        $this->mock->should_not_receive('foo');
        $this->expectException(Invalid_Count_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testShouldNotReceiveWithArgumentThrowsExceptionIfMethodCalled(): void
    {
        $this->mock->should_not_receive('foo')
            ->with(2);
        $this->expectException(Invalid_Count_Exception::class);
        $this->mock->foo(2);
        Mockery::close();
    }

    public function testStringConstraintMatchesArgument(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('string'))->once();
        $this->mock->foo('2');
    }

    public function testStringConstraintNonMatchingCase(): void
    {
        $this->mock->should_receive('foo')
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(1, Mockery::type('string'))->never();
        $this->mock->foo();
        $this->mock->foo(1);
        $this->mock->foo(1, 2, 3);
    }

    public function testStringConstraintThrowsExceptionWhenConstraintUnmatched(): void
    {
        $this->mock->should_receive('foo')
            ->with(Mockery::type('string'));
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(1);
        Mockery::close();
    }

    public function testThrowsException(): void
    {
        $this->mock->should_receive('foo')
            ->and_throw(new OutOfBoundsException());
        $this->expectException(OutOfBoundsException::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testThrowsExceptionBasedOnArgs(): void
    {
        $this->mock->should_receive('foo')
            ->and_throw('OutOfBoundsException');
        $this->expectException(OutOfBoundsException::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testThrowsExceptionBasedOnArgsWithMessage(): void
    {
        $this->mock->should_receive('foo')
            ->and_throw('OutOfBoundsException', 'foo');
        try {
            $this->mock->foo();
        } catch (OutOfBoundsException $e) {
            self::assertSame('foo', $e->getMessage());
        }
    }

    public function testThrowsExceptionOnNoArgumentMatch(): void
    {
        $this->mock->should_receive('foo')
            ->with(1);
        $this->expectException(\Mockery\Exception::class);
        $this->mock->foo(2);
        Mockery::close();
    }

    public function testThrowsExceptionSequentially(): void
    {
        $this->mock->should_receive('foo')
            ->and_throw(new \Exception())
            ->and_throw(new OutOfBoundsException());

        try {
            $this->mock->foo();
        } catch (\Exception $e) {
        }

        $this->expectException(OutOfBoundsException::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testTimesCountCallThrowsExceptionOnTooFewCalls(): void
    {
        $this->mock->should_receive('foo')
            ->times(2);
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        Mockery::close();
    }

    public function testTimesCountCallThrowsExceptionOnTooManyCalls(): void
    {
        $this->mock->should_receive('foo')
            ->times(2);
        $this->mock->foo();
        $this->mock->foo();
        $this->expectException(Count_Validator_Exception::class);
        $this->mock->foo();
        Mockery::close();
    }

    public function testTimesCountCalls(): void
    {
        $this->mock->should_receive('foo')
            ->times(4);
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
        $this->mock->foo();
    }

    public function testTimesExpectationForbidsFloatNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mock->should_receive('foo')
            ->times(1.3);
        Mockery::close();
    }

    public function testToStringMagicMethodCanBeMocked(): void
    {
        $this->mock->should_receive('__toString')
            ->and_return('dave');
        self::assertSame("{$this->mock}", 'dave');
    }

    public function testUnorderedCallsIgnoredForOrdering(): void
    {
        $this->mock->should_receive('foo')
            ->with(1)
            ->ordered()
            ->once();
        $this->mock->should_receive('foo')
            ->with(2)
            ->times(3);
        $this->mock->should_receive('foo')
            ->with(3)
            ->ordered()
            ->once();
        $this->mock->foo(2);
        $this->mock->foo(1);
        $this->mock->foo(2);
        $this->mock->foo(3);
        $this->mock->foo(2);
    }

    public function testWetherMockWithInterfaceOnlyCanNotImplementNonExistingMethods(): void
    {
        Mockery::get_configuration()->allow_mocking_non_existent_methods(false);
        $waterMock = Mockery::mock(IWater::class);
        $this->expectException(\Mockery\Exception::class);
        $waterMock
            ->should_receive('nonExistentMethod')
            ->once()
            ->and_return_null();
        Mockery::close();
    }
}
