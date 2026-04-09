<?php

declare(strict_types=1);

namespace CarriageDB\CRDT;

class GSet
{
    /**
     * @var array<int, mixed>
     */
    private array $elements;

    /**
     * @param  array<int, mixed>  $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;

        sort($this->elements);
    }

    public function add(mixed $element): self
    {
        if ($this->has($element)) {
            return $this;
        }

        $this->elements[] = $element;

        sort($this->elements);

        return $this;
    }

    public function merge(GSet $otherReplica): GSet
    {
        $arr = array_merge($this->elements, $otherReplica->elements)
            |> (fn($merge) => array_unique($merge, SORT_REGULAR));

        return new GSet($arr);
    }

    public function count(): int
    {
        return count($this->elements);
    }

    public function has(mixed $element): bool
    {
        return in_array($element, $this->elements, true);
    }

    /**
     * @return array<int, mixed>
     */
    public function elements(): array
    {
        return $this->elements;
    }
}
