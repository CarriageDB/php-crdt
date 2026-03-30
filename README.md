# PHP CRDT

Conflict-free Replicated Data Types (CRDT) for PHP.

## What is a CRDT?

A CRDT is a data structure that allows multiple replicas of a distributed system to maintain consistency without having
a centralized coordinator. This means that CRDTs can be used in peer-to-peer setups in addition to more traditional PHP
applications that have a central server.

Systems that use CRDTs can ensure strong eventual consistency. This means that every replica will eventually have the
exact same state as any other replica, no matter what order the updates arrive in, and without any conflicts. This is
achieved through the use of merge operations that are commutative and associative.

> Commutative operations are ones that can be performed in any order without changing the result. Addition is an example
> of a commutative operation because you can add numbers in any order and get the same result: `a + b = b + a`
>
> Associative operations are ones where the grouping of operations can be changed without changing the result. Addition
> is also an associative operation, because the order in which you add any number of values does not matter:
> `a + (b + c) = (a + b) + c`

## Why might you use CRDTs?

CRDTs are useful in scenarios where you need to maintain consistency across multiple replicas in a distributed system,
especially when there is no central authority to coordinate updates. If you are working with data that users may modify
while they are offline, and you wish to ensure that concurrent changes are merged consistently, CRDTs may be useful.

## Data Types

### G-Counter

The Grow-only Counter, `CarriageDB/CRDT/Counters/GCounter`, can be incremented and merged with other G-Counters. It
mostly exists as an example of a simple CRDT.

### PN-Counter

The Positive-Negative Counter, `CarriageDB/CRDT/Counters/PNCounter`, is similar to the G-Counter, but it can also be
decremented. It mostly exists as an example of making more complex CRDTs by building on top of simpler data types.

## Development

```bash
git clone https://github.com/CarriageDB/php-crdt.git

composer install

# After making changes, ensure that all checks and tests are passing.i

composer test
```
