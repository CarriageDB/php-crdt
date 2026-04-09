<?php

declare(strict_types=1);

namespace CarriageDB\CRDT;

class TwoPSet
{
    private GSet $elements;

    private GSet $tombstones;

    public function __construct(?GSet $elements = null, ?GSet $tombstones = null)
    {
        $this->elements = $elements ?? new GSet();
        $this->tombstones = $tombstones ?? new GSet();
    }

    public function add(string $element): self
    {
        $this->elements->add($element);

        return $this;
    }

    public function remove(string $element): self
    {
        $this->tombstones->add($element);

        return $this;
    }

    public function merge(TwoPSet $otherReplica): TwoPSet
    {
        $newElements = $this->elements->merge($otherReplica->elements);

        $newTombstones = $this->tombstones->merge($otherReplica->tombstones);

        return new TwoPSet($newElements, $newTombstones);
    }

    public function count(): int
    {
        return count($this->elements());
    }

    public function has(string $element): bool
    {
        return $this->elements->has($element) && ! $this->tombstones->has($element);
    }

    /**
     * @return array<int, mixed>
     */
    public function elements(): array
    {
        return array_filter(
            $this->elements->elements(),
            fn ($element) => ! $this->tombstones->has($element)
        );
    }
}
