<?php

declare(strict_types=1);

namespace Tests;

use CarriageDB\CRDT\GSet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GSetTest extends TestCase
{
    #[Test]
    public function setStartsEmpty(): void
    {
        $set = new GSet();

        $this->assertEquals(0, $set->count());
        $this->assertEquals([], $set->elements());
    }

    #[Test]
    public function stringElementsCanBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add('apple');

        $this->assertEquals(1, $set->count());
        $this->assertEquals(['apple'], $set->elements());
    }

    #[Test]
    public function multipleStringElementsCanBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add('apple')
            ->add('banana')
            ->add('cherry');

        $this->assertEquals(3, $set->count());
        $this->assertEquals(['apple', 'banana', 'cherry'], $set->elements());
    }

    #[Test]
    public function duplicateStringElementsCannotBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add('apple')
            ->add('apple');

        $this->assertEquals(1, $set->count());
        $this->assertEquals(['apple'], $set->elements());
    }

    #[Test]
    public function integerElementsCanBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add(4);

        $this->assertEquals(1, $set->count());
        $this->assertEquals([4], $set->elements());
    }

    #[Test]
    public function multipleIntegerElementsCanBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add(4)
            ->add(12)
            ->add(9);

        $this->assertEquals(3, $set->count());
        $this->assertEquals([4, 9, 12], $set->elements());
    }

    #[Test]
    public function duplicateIntegerElementsCannotBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add(5)
            ->add(5);

        $this->assertEquals(1, $set->count());
        $this->assertEquals([5], $set->elements());
    }

    #[Test]
    public function mixedTypeElementsCanBeAddedToTheSet(): void
    {
        $set = new GSet();

        $set->add(5)
            ->add('5')
            ->add('five');

        $this->assertEquals(3, $set->count());
        $this->assertEquals([5, '5', 'five'], $set->elements());
    }

    #[Test]
    public function mixedTypeElementsCanBeMergedBetweenSets(): void
    {
        $setA = new GSet([5]);

        $setB = new GSet(['5', 'five']);

        $mergedSet = $setA->merge($setB);

        $this->assertEquals(3, $mergedSet->count());
        $this->assertEquals([5, '5', 'five'], $mergedSet->elements());
    }

    /**
     * @param  array<int, mixed>  $a
     * @param  array<int, mixed>  $b
     * @param  array<int, mixed>  $expected
     * @return void
     */
    #[Test]
    #[DataProvider('mergingProvider')]
    public function twoSetsCanBeMerged(array $a, array $b, array $expected): void
    {
        $setA = new GSet($a);
        $setB = new GSet($b);

        $aFirstMerge = $setA->merge($setB);
        $bFirstMerge = $setB->merge($setA);

        $this->assertEquals(count($expected), $aFirstMerge->count());
        $this->assertEquals($expected, $aFirstMerge->elements());

        $this->assertEquals(count($expected), $bFirstMerge->count());
        $this->assertEquals($expected, $bFirstMerge->elements());

        $this->assertArraysAreEqual(array_values($aFirstMerge->elements()), array_values($bFirstMerge->elements()));
    }

    /**
     * @return array<array<array<int, mixed>>>
     */
    public static function mergingProvider(): array
    {
        return [
            [[], [], []],
            [['apple', 'banana'], [], ['apple', 'banana']],
            [[], ['cherry'], ['cherry']],
            [['apple', 'banana'], ['cherry'], ['apple', 'banana', 'cherry']],
            [[5], [6], [5, 6]],
        ];
    }

    #[Test]
    public function threeSetsCanBeMerged(): void
    {
        $setA = new GSet([
            'apple',
            'cherry',
        ]);

        $setB = new GSet([
            'banana',
        ]);

        $setC = new GSet([
            'apple',
            'cherry',
            'durian',
        ]);

        $abcMerge = $setA->merge($setB);
        $abcMerge = $abcMerge->merge($setC);

        $acbMerge = $setA->merge($setC);
        $acbMerge = $acbMerge->merge($setB);

        $bacMerge = $setB->merge($setA);
        $bacMerge = $bacMerge->merge($setC);

        $bcaMerge = $setB->merge($setC);
        $bcaMerge = $bcaMerge->merge($setA);

        $cabMerge = $setC->merge($setA);
        $cabMerge = $cabMerge->merge($setB);

        $cbaMerge = $setC->merge($setB);
        $cbaMerge = $cbaMerge->merge($setA);

        $this->assertEquals(4, $abcMerge->count());
        $this->assertEquals(4, $acbMerge->count());
        $this->assertEquals(4, $bacMerge->count());
        $this->assertEquals(4, $bcaMerge->count());
        $this->assertEquals(4, $cabMerge->count());
        $this->assertEquals(4, $cbaMerge->count());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $abcMerge->elements());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $acbMerge->elements());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $bacMerge->elements());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $bcaMerge->elements());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $cabMerge->elements());
        $this->assertEquals(['apple', 'banana', 'cherry', 'durian'], $cbaMerge->elements());
    }

    #[Test]
    public function mergingLeavesOriginalSetsUnchanged(): void
    {
        $setA = new GSet(['apple']);
        $setB = new GSet(['banana']);

        $mergedSet = $setA->merge($setB);

        $this->assertEquals(['apple'], $setA->elements());
        $this->assertEquals(['banana'], $setB->elements());
        $this->assertEquals(['apple', 'banana'], $mergedSet->elements());
    }
}
