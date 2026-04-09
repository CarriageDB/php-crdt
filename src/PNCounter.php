<?php

declare(strict_types=1);

namespace CarriageDB\CRDT;

class PNCounter
{
    private GCounter $incrementCounts;

    private GCounter $decrementCounts;

    private string $key;

    public function __construct(string $key, ?GCounter $incrementCounts = null, ?GCounter $decrementCounts = null)
    {
        $this->incrementCounts = $incrementCounts ?? new GCounter($key);
        $this->decrementCounts = $decrementCounts ?? new GCounter($key);
        $this->key = $key;
    }

    public function increment(int $count = 1): self
    {
        $this->incrementCounts->increment($count);

        return $this;
    }

    public function decrement(int $count = 1): self
    {
        $this->decrementCounts->increment($count);

        return $this;
    }

    public function merge(PNCounter $otherReplica): PNCounter
    {
        $newIncrementCounts = $this->incrementCounts->merge($otherReplica->incrementCounts);
        $newDecrementCounts = $this->decrementCounts->merge($otherReplica->decrementCounts);

        return new PNCounter($this->key, $newIncrementCounts, $newDecrementCounts);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getState(): array
    {
        return [
            'increment' => $this->incrementCounts->getState(),
            'decrement' => $this->decrementCounts->getState(),
        ];
    }

    public function value(): int
    {
        return $this->incrementCounts->value() - $this->decrementCounts->value();
    }
}
