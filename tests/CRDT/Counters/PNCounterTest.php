<?php
declare(strict_types=1);

namespace Tests\CRDT\Counters;

use CarriageDB\CRDT\Counters\PNCounter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PNCounterTest extends TestCase
{
    public function test_counter_starts_at_zero(): void
    {
        $counter = new PNCounter('A');

        $this->assertEquals(0, $counter->getValue());
    }

    public function test_counter_can_be_incremented(): void
    {
        $counter = new PNCounter('A');

        $counter->increment();

        $this->assertEquals(1, $counter->getValue());
    }

    public function test_counter_can_be_decremented(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement();

        $this->assertEquals(-1, $counter->getValue());
    }

    public function test_counter_can_be_incremented_multiple_times(): void
    {
        $counter = new PNCounter('A');

        $counter->increment()
            ->increment()
            ->increment();

        $this->assertEquals(3, $counter->getValue());
    }

    public function test_counter_can_be_decremented_multiple_times(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement()
            ->decrement()
            ->decrement();

        $this->assertEquals(-3, $counter->getValue());
    }

    public function test_counter_can_be_incremented_with_increased_count(): void
    {
        $counter = new PNCounter('A');

        $counter->increment(5);

        $this->assertEquals(5, $counter->getValue());
    }

    public function test_counter_can_be_decremented_with_increased_count(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement(5);

        $this->assertEquals(-5, $counter->getValue());
    }

    public function test_counter_can_be_incremented_multiple_times_with_increased_count(): void
    {
        $counter = new PNCounter('A');

        $counter->increment(4)
            ->increment(7)
            ->increment(2);

        $this->assertEquals(13, $counter->getValue());
    }



    public function test_counter_can_be_decremented_multiple_times_with_increased_count(): void
    {
        $counter = new PNCounter('A');

        $counter->decrement(4)
            ->decrement(7)
            ->decrement(2);

        $this->assertEquals(-13, $counter->getValue());
    }

    #[DataProvider('invalidCountProvider')]
    public function test_counter_cannot_be_incremented_with_invalid_counts(int $count): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be positive');

        $counter = new PNCounter('A');

        $counter->increment($count);
    }

    #[DataProvider('invalidCountProvider')]
    public function test_counter_cannot_be_decremented_with_invalid_counts(int $count): void
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

    #[DataProvider('mergingProvider')]
    public function test_cross_merging_two_counters(int $aInc, int $aDec, int $bInc, int $bDec, int $expected): void
    {
        $counterA = new PNCounter('A');
        if ($aInc > 0) $counterA->increment($aInc);
        if ($aDec > 0) $counterA->decrement($aDec);

        $counterB = new PNCounter('B');
        if ($bInc > 0) $counterB->increment($bInc);
        if ($bDec > 0) $counterB->decrement($bDec);

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

    public function test_merging_leaves_original_counters_unchanged(): void
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
