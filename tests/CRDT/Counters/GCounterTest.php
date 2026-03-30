<?php
declare(strict_types=1);

namespace Tests\CRDT\Counters;

use CarriageDB\CRDT\Counters\GCounter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GCounterTest extends TestCase
{
    public function test_counter_starts_at_zero(): void
    {
        $counter = new GCounter('A');

        $this->assertEquals(0, $counter->getValue());
    }

    public function test_counter_can_be_incremented(): void
    {
        $counter = new GCounter('A');

        $counter->increment();

        $this->assertEquals(1, $counter->getValue());
    }

    public function test_counter_can_be_incremented_multiple_times(): void
    {
        $counter = new GCounter('A');

        $counter->increment()
            ->increment()
            ->increment();

        $this->assertEquals(3, $counter->getValue());
    }

    public function test_counter_can_be_incremented_with_increased_count(): void
    {
        $counter = new GCounter('A');

        $counter->increment(5);

        $this->assertEquals(5, $counter->getValue());
    }

    public function test_counter_can_be_incremented_multiple_times_with_increased_count(): void
    {
        $counter = new GCounter('A');

        $counter->increment(4)
            ->increment(7)
            ->increment(2);

        $this->assertEquals(13, $counter->getValue());
    }

    #[DataProvider('invalidCountProvider')]
    public function test_counter_cannot_be_incremented_with_invalid_counts(int $count): void
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

    #[DataProvider('mergingProvider')]
    public function test_cross_merging_two_counters(int $a, int $b, int $expected): void
    {
        $counterA = new GCounter('A');
        $counterB = new GCounter('B');

        if ($a > 0) $counterA->increment($a);
        if ($b > 0) $counterB->increment($b);

        $aFirstMerge = $counterA->merge($counterB);
        $bFirstMerge = $counterB->merge($counterA);

        $this->assertEquals($a, $counterA->getValue());
        $this->assertEquals($b, $counterB->getValue());
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
            [0, 0, 0],
            [0, 2, 2],
            [3, 0, 3],
            [4, 9, 13],
            [8, 5, 13],
            [7, 7, 14],
        ];
    }

    public function test_merging_leaves_original_counters_unchanged(): void
    {
        $counterA = new GCounter('A');
        $counterB = new GCounter('B');

        $counterA->increment(5);
        $counterB->increment(3);

        $mergedCounter = $counterA->merge($counterB);

        $this->assertEquals(5, $counterA->getValue());
        $this->assertEquals(3, $counterB->getValue());
        $this->assertEquals(8, $mergedCounter->getValue());
    }
}
