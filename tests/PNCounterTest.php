<?php

declare(strict_types=1);

namespace Tests;

use CarriageDB\CRDT\PNCounter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PNCounterTest extends TestCase
{
    #[Test]
    public function counterStartsAtZero(): void
    {
        $counter = new PNCounter('A');

        $this->assertEquals(0, $counter->getValue());
    }

    #[Test]
    public function counterCanBeIncremented(): void
    {
        $counter = new PNCounter('A');

        $counter->increment();

        $this->assertEquals(1, $counter->getValue());
    }

    #[Test]
    public function counterCanBeDecremented(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement();

        $this->assertEquals(-1, $counter->getValue());
    }

    #[Test]
    public function counterCanBeIncrementedMultipleTimes(): void
    {
        $counter = new PNCounter('A');

        $counter->increment()
            ->increment()
            ->increment();

        $this->assertEquals(3, $counter->getValue());
    }

    #[Test]
    public function counterCanBeDecrementedMultipleTimes(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement()
            ->decrement()
            ->decrement();

        $this->assertEquals(-3, $counter->getValue());
    }

    #[Test]
    public function counterCanBeIncrementedWithIncreasedCount(): void
    {
        $counter = new PNCounter('A');

        $counter->increment(5);

        $this->assertEquals(5, $counter->getValue());
    }

    #[Test]
    public function counterCanBeDecrementedWithIncreasedCount(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement(5);

        $this->assertEquals(-5, $counter->getValue());
    }

    #[Test]
    public function counterCanBeIncrementedMultipleTimesWithIncreasedCount(): void
    {
        $counter = new PNCounter('A');

        $counter->increment(4)
            ->increment(7)
            ->increment(2);

        $this->assertEquals(13, $counter->getValue());
    }

    #[Test]
    public function counterCanBeDecrementedMultipleTimesWithIncreasedCount(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement(4)
            ->decrement(7)
            ->decrement(2);

        $this->assertEquals(-13, $counter->getValue());
    }

    #[Test]
    #[DataProvider('invalidCountProvider')]
    public function counterCannotBeIncrementedWithInvalidCounts(int $count): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be positive');

        $counter = new PNCounter('A');

        $counter->increment($count);
    }

    #[Test]
    #[DataProvider('invalidCountProvider')]
    public function counterCannotBeDecrementedWithInvalidCounts(int $count): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be positive');

        $counter = new PNCounter('A');

        $counter->decrement($count);
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
    public function twoCountersCanBeMerged(int $aInc, int $aDec, int $bInc, int $bDec, int $expected): void
    {
        $counterA = new PNCounter('A');
        if ($aInc > 0) {
            $counterA->increment($aInc);
        }
        if ($aDec > 0) {
            $counterA->decrement($aDec);
        }

        $counterB = new PNCounter('B');
        if ($bInc > 0) {
            $counterB->increment($bInc);
        }
        if ($bDec > 0) {
            $counterB->decrement($bDec);
        }

        $aFirstMerge = $counterA->merge($counterB);
        $bFirstMerge = $counterB->merge($counterA);

        $this->assertEquals($aInc - $aDec, $counterA->getValue());
        $this->assertEquals($bInc - $bDec, $counterB->getValue());
        $this->assertEquals($expected, $aFirstMerge->getValue());
        $this->assertEquals($expected, $bFirstMerge->getValue());
        $this->assertArraysAreEqual($aFirstMerge->getState(), $bFirstMerge->getState());
    }

    /**
     * @return int[][]
     */
    public static function mergingProvider(): array
    {
        return [
            [0, 0, 0, 0, 0],
            [3, 0, 0, 0, 3],
            [4, 0, 9, 0, 13],
            [7, 0, 7, 0, 14],
            [0, 3, 0, 0, -3],
            [0, 8, 0, 5, -13],
            [0, 7, 0, 7, -14],
            [5, 2, 0, 0, 3],
            [8, 0, 0, 2, 6],
            [0, 8, 2, 0, -6],
            [10, 3, 7, 4, 10],
        ];
    }

    #[Test]
    public function threeCountersCanBeMerged(): void
    {
        $counterA = new PNCounter('A');
        $counterA->increment(25);
        $counterA->decrement(7);

        $counterB = new PNCounter('B');
        $counterB->increment(3);
        $counterB->decrement(9);

        $counterC = new PNCounter('C');
        $counterC->increment(12);
        $counterC->decrement(11);

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

        $this->assertEquals(13, $abcMerge->getValue());
        $this->assertEquals(13, $acbMerge->getValue());
        $this->assertEquals(13, $bacMerge->getValue());
        $this->assertEquals(13, $bcaMerge->getValue());
        $this->assertEquals(13, $cabMerge->getValue());
        $this->assertEquals(13, $cbaMerge->getValue());
        $this->assertArraysAreEqual($abcMerge->getState(), $acbMerge->getState());
        $this->assertArraysAreEqual($acbMerge->getState(), $bacMerge->getState());
        $this->assertArraysAreEqual($bacMerge->getState(), $bcaMerge->getState());
        $this->assertArraysAreEqual($bcaMerge->getState(), $cabMerge->getState());
        $this->assertArraysAreEqual($cabMerge->getState(), $cbaMerge->getState());
    }

    #[Test]
    public function mergingLeavesOriginalCountersUnchanged(): void
    {
        $counterA = new PNCounter('A');
        $counterA->increment(5);
        $counterA->decrement(2);

        $counterB = new PNCounter('B');
        $counterB->increment(12);
        $counterB->decrement(6);

        $mergedCounter = $counterA->merge($counterB);

        $this->assertEquals(3, $counterA->getValue());
        $this->assertEquals(6, $counterB->getValue());
        $this->assertEquals(9, $mergedCounter->getValue());
    }
}
