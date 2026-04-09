<?php

declare(strict_types=1);

namespace Tests;

use CarriageDB\CRDT\GCounter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GCounterTest extends TestCase
{
    #[Test]
    public function counterStartsAtZero(): void
    {
        $counter = new GCounter('A');

        $this->assertEquals(0, $counter->value());
    }

    #[Test]
    public function counterCanBeIncremented(): void
    {
        $counter = new GCounter('A');

        $counter->increment();

        $this->assertEquals(1, $counter->value());
    }

    #[Test]
    public function counterCanBeIncrementedMultipleTimes(): void
    {
        $counter = new GCounter('A');

        $counter->increment()
            ->increment()
            ->increment();

        $this->assertEquals(3, $counter->value());
    }

    #[Test]
    public function counterCanBeIncrementedWithIncreasedCount(): void
    {
        $counter = new GCounter('A');

        $counter->increment(5);

        $this->assertEquals(5, $counter->value());
    }

    #[Test]
    public function counterCanBeIncrementedMultipleTimesWithIncreasedCount(): void
    {
        $counter = new GCounter('A');

        $counter->increment(4)
            ->increment(7)
            ->increment(2);

        $this->assertEquals(13, $counter->value());
    }

    #[Test]
    #[DataProvider('invalidCountProvider')]
    public function counterCannotBeIncrementedWithInvalidCounts(int $count): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be positive');

        $counter = new GCounter('A');

        $counter->increment($count);
    }

    /**
     * @return int[][]
     */
    public static function invalidCountProvider(): array
    {
        return [
            [0],
            [-1],
            [-5]
        ];
    }

    #[Test]
    #[DataProvider('mergingProvider')]
    public function twoCountersCanBeMerged(int $a, int $b, int $expected): void
    {
        $counterA = new GCounter('A');
        $counterB = new GCounter('B');

        if ($a > 0) {
            $counterA->increment($a);
        }
        if ($b > 0) {
            $counterB->increment($b);
        }

        $aFirstMerge = $counterA->merge($counterB);
        $bFirstMerge = $counterB->merge($counterA);

        $this->assertEquals($a, $counterA->value());
        $this->assertEquals($b, $counterB->value());
        $this->assertEquals($expected, $aFirstMerge->value());
        $this->assertEquals($expected, $bFirstMerge->value());
        $this->assertArraysAreEqual($aFirstMerge->getState(), $bFirstMerge->getState());
    }

    /**
     * @return int[][]
     */
    public static function mergingProvider(): array
    {
        return [
            [0, 0, 0],
            [0, 2, 2],
            [3, 0, 3],
            [4, 9, 13],
            [8, 5, 13],
            [7, 7, 14],
        ];
    }

    #[Test]
    public function threeCountersCanBeMerged(): void
    {
        $counterA = new GCounter('A');
        $counterA->increment(25);

        $counterB = new GCounter('B');
        $counterB->increment(3);

        $counterC = new GCounter('C');
        $counterC->increment(12);

        $abcMerge = $counterA->merge($counterB);
        $abcMerge = $abcMerge->merge($counterC);

        $acbMerge = $counterA->merge($counterC);
        $acbMerge = $acbMerge->merge($counterB);

        $bacMerge = $counterB->merge($counterA);
        $bacMerge = $bacMerge->merge($counterC);

        $bcaMerge = $counterB->merge($counterC);
        $bcaMerge = $bcaMerge->merge($counterA);

        $cabMerge = $counterC->merge($counterA);
        $cabMerge = $cabMerge->merge($counterB);

        $cbaMerge = $counterC->merge($counterB);
        $cbaMerge = $cbaMerge->merge($counterA);

        $this->assertEquals(40, $abcMerge->value());
        $this->assertEquals(40, $acbMerge->value());
        $this->assertEquals(40, $bacMerge->value());
        $this->assertEquals(40, $bcaMerge->value());
        $this->assertEquals(40, $cabMerge->value());
        $this->assertEquals(40, $cbaMerge->value());
        $this->assertArraysAreEqual($abcMerge->getState(), $acbMerge->getState());
        $this->assertArraysAreEqual($acbMerge->getState(), $bacMerge->getState());
        $this->assertArraysAreEqual($bacMerge->getState(), $bcaMerge->getState());
        $this->assertArraysAreEqual($bcaMerge->getState(), $cabMerge->getState());
        $this->assertArraysAreEqual($cabMerge->getState(), $cbaMerge->getState());
    }

    #[Test]
    public function mergingLeavesOriginalCountersUnchanged(): void
    {
        $counterA = new GCounter('A');
        $counterB = new GCounter('B');

        $counterA->increment(5);
        $counterB->increment(3);

        $mergedCounter = $counterA->merge($counterB);

        $this->assertEquals(5, $counterA->value());
        $this->assertEquals(3, $counterB->value());
        $this->assertEquals(8, $mergedCounter->value());
    }
}
