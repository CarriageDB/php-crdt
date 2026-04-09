<?php

declare(strict_types=1);

namespace CarriageDB\CRDT;

use InvalidArgumentException;

class GCounter
{
    /**
     * @var array<string, int>
     */
    private array $counts;

    private string $key;

    /**
     * @param  string  $key
     * @param  array<string, int>  $counts
     */
    public function __construct(string $key, array $counts = [])
    {
        $this->counts = $counts;
        $this->key = $key;
    }

    public function increment(int $count = 1): self
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be positive');
        }

        $value = $this->counts[$this->key] ?? 0;
        $this->counts[$this->key] = $value + $count;

        return $this;
    }

    public function merge(GCounter $otherReplica): GCounter
    {
        $newCounts = $this->counts;
        foreach ($otherReplica->counts as $key => $otherValue) {
            $localValue = $newCounts[$key] ?? 0;
            $newCounts[$key] = max($localValue, $otherValue);
        }

        return new GCounter($this->key, $newCounts);
    }

    /**
     * @return array<string, int>
     */
    public function getState(): array
    {
        return $this->counts;
    }

    public function value(): int
    {
        return array_sum($this->counts);
    }
}
