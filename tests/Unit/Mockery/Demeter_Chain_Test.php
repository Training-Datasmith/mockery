<?php

declare(strict_types=1);

namespace Tests\Unit\Mockery;

use mock1;
use mock2;
use Mockery;
use Mockery\Adapter\Phpunit\Mockery_Test_Case;
use Mockery\Mock;
use stdClass;

/**
 * @coversDefaultClass \Mockery
 */
final class Demeter_Chain_Test extends Mockery_Test_Case
{
    /**
     * @var Mock
     */
    private $mock;

    protected function mockery_test_set_up(): void
    {
        $this->mock = Mockery::mock()->should_ignore_missing();
    }

    public function mockery_test_tear_down(): void
    {
        $this->mock->mockery_get_container()
            ->mockery_close();
    }

    public function testDemeterChain(): void
    {
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->and_return('somethingElse');

        self::assertSame('somethingElse', $this->mock->getElement()->getFirst());
    }

    /**
     * @requires PHP >=8.0
     */
    public function testDemeterChainsOnInterfaceSubjects(): void
    {
        $mock = Mockery::mock(\PHP80\PHP80TestInterface::class);

        $mock->should_receive('do->thing');

        self::assertInstanceOf(\PHP80\PHP80TestInterface::class, $mock->do()->thing());
    }

    public function testDemeterChainsWithClassReturnTypeHints(): void
    {
        $a = Mockery::mock(\DemeterChain\A::class);
        $a->should_receive('foo->bar->baz')
            ->and_return(new stdClass());

        $m = new \DemeterChain\Main();
        $result = $m->callDemeter($a);

        self::assertInstanceOf(stdClass::class, $result);
    }

    public function testManyChains(): void
    {
        $this->mock->should_receive('getElements->getFirst')
            ->once()
            ->and_return('something');

        $this->mock->should_receive('getElements->getSecond')
            ->once()
            ->and_return('somethingElse');

        $this->mock->getElements()
            ->getFirst();
        $this->mock->getElements()
            ->getSecond();
    }

    public function testMultiLevelDemeterChain(): void
    {
        $this->mock->should_receive('levelOne->levelTwo->getFirst')
            ->and_return('first');

        $this->mock->should_receive('levelOne->levelTwo->getSecond')
            ->and_return('second');

        self::assertSame('second', $this->mock->levelOne() ->levelTwo() ->getSecond());
        self::assertSame('first', $this->mock->levelOne() ->levelTwo() ->getFirst());
    }

    public function testMultipleDemeterChainsWithClassReturnTypeHints(): void
    {
        $bar = new \DemeterChain\C();
        $qux = new \DemeterChain\C();
        $a = Mockery::mock(\DemeterChain\A::class);
        $a->should_receive('foo->bar')
            ->and_return($bar);
        $a->should_receive('foo->qux')
            ->and_return($qux);
        self::assertSame($bar, $a->foo()->bar());
        self::assertSame($qux, $a->foo()->qux());
    }

    public function testSimilarDemeterChainsOnDifferentClasses(): void
    {
        $mock1 = Mockery::mock('overload:mock1');
        $mock1->should_receive('select->some->data')
            ->and_return(1);
        $mock1->should_receive('select->some->other->data')
            ->and_return(2);

        $mock2 = Mockery::mock('overload:mock2');
        $mock2->should_receive('select->some->data')
            ->and_return(3);
        $mock2->should_receive('select->some->other->data')
            ->and_return(4);

        self::assertSame(1, mock1::select()->some()->data());
        self::assertSame(2, mock1::select()->some()->other()->data());
        self::assertSame(3, mock2::select()->some()->data());
        self::assertSame(4, mock2::select()->some()->other()->data());
    }

    public function testThreeChains(): void
    {
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->and_return('something');

        $this->mock->should_receive('getElement->getSecond')
            ->once()
            ->and_return('somethingElse');

        self::assertSame('something', $this->mock->getElement() ->getFirst());
        self::assertSame('somethingElse', $this->mock->getElement() ->getSecond());
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->and_return('somethingNew');
        self::assertSame('somethingNew', $this->mock->getElement() ->getFirst());
    }

    public function testTwoChains(): void
    {
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->and_return('something');

        $this->mock->should_receive('getElement->getSecond')
            ->once()
            ->and_return('somethingElse');

        self::assertSame('something', $this->mock->getElement() ->getFirst());
        self::assertSame('somethingElse', $this->mock->getElement() ->getSecond());
        $this->mock->mockery_get_container()
            ->mockery_close();
    }

    public function testTwoChainsWithExpectedParameters(): void
    {
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->with('parameter')
            ->and_return('something');

        $this->mock->should_receive('getElement->getSecond')
            ->once()
            ->with('secondParameter')
            ->and_return('somethingElse');

        self::assertSame('something', $this->mock->getElement() ->getFirst('parameter'));
        self::assertSame('somethingElse', $this->mock->getElement() ->getSecond('secondParameter'));
        $this->mock->mockery_get_container()
            ->mockery_close();
    }

    public function testTwoNotRelatedChains(): void
    {
        $this->mock->should_receive('getElement->getFirst')
            ->once()
            ->and_return('something');

        $this->mock->should_receive('getOtherElement->getSecond')
            ->once()
            ->and_return('somethingElse');

        self::assertSame('somethingElse', $this->mock->getOtherElement() ->getSecond());
        self::assertSame('something', $this->mock->getElement()->getFirst());
    }
}
