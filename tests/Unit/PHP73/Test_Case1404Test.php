<?php

declare(strict_types=1);

namespace Tests\Unit\PHP73;

use Generator;
use Mockery;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mockery
 * @requires PHP 7.3
 * @see https://github.com/mockery/mockery/issues/1404
 */
final class Test_Case1404_Test extends TestCase
{
    /**
     * @return Generator<string,list<string>>
     */
    public static function provideResult(): iterable
    {
        yield from [
            'empty' => [[]],
            'non-empty' => [['Black', 'Lives', 'Matter']],
        ];
    }

    /**
     * @dataProvider provideResult
     */
    public function testDemeterChainsAlternativeSyntax(array $result): void
    {
        $dbConnection = Mockery::mock(PDO::class);

        $dbConnection->should_receive('query->fetchAll')
            ->and_return($result);

        self::assertSame($result, $dbConnection->query('sql')->fetchAll());
    }

    /**
     * @dataProvider provideResult
     */
    public function testDemeterChainsExpects(array $result): void
    {
        $dbConnection = Mockery::mock(PDO::class);

        $dbConnection->expects('query->fetchAll')
            ->and_return($result);

        self::assertSame($result, $dbConnection->query('sql')->fetchAll());
    }

    /**
     * @dataProvider provideResult
     */
    public function testDetestDemeterChainsAllowsmeterChainsAllows(array $result): void
    {
        $dbConnection = Mockery::mock(PDO::class);

        $dbConnection->allows('query->fetchAll')
            ->and_return($result);

        self::assertSame($result, $dbConnection->query('sql')->fetchAll());
    }

    /**
     * @dataProvider provideResult
     */
    public function testNonDemeterChainsSyntax(array $result): void
    {
        $dbStatement = Mockery::mock(PDOStatement::class);
        $dbStatement->expects('fetchAll')
            ->and_return($result);

        $dbConnection = Mockery::mock(PDO::class);
        $dbConnection->expects('query')
            ->with('sql')
            ->and_return($dbStatement);

        self::assertSame($result, $dbConnection->query('sql')->fetchAll());
    }
}
