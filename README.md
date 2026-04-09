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

A counter needs a unique identifier to ensure consistency. Reusing identifiers can lead to data loss.

```php
$counterA = new GCounter('idA');

$counterA->increment();
$counterA->increment(3);

$counterA->value(); // === 4

$counterB = new GCounter('idB');

$counterB->increment();

$counterB->value(); // === 1

$mergeCounter = $counterA->merge($counterB);

$mergeCounter->value(); // === 5
```

A G-Counter works by tracking how many times each uniquely identified replica has recorded an increment operation. When
merging, the largest value for each known replica is used. Because this is a grow-only counter, we know that the larger
value is the most recent value. The sum of each replica count is the value of the counter.

A G-Counter can be initialized with starting values.

```php
$data = [
    'idA' => 4,
    'idB' => 0,
    'idC' => 17,
];

$counter = new GCounter('idD', $data);

$counter->value(); // === 21
```

### PN-Counter

The Positive-Negative Counter, `CarriageDB/CRDT/Counters/PNCounter`, is similar to the G-Counter, but it can also be
decremented. It mostly exists as an example of making more complex CRDTs by building on top of simpler data types.

A counter needs a unique identifier to ensure consistency. Reusing identifiers can lead to data loss.

```php
$counterA = new PNCounter('idA');

$counterA->increment(2);
$counterA->decrement();

$counterA->value(); // === 1

$counterB = new GCounter('idB');

$counterB->increment();
$counterB->decrement(3);

$counterB->value(); // === -2

$mergeCounter = $counterA->merge($counterB);

$mergeCounter->value(); // === -1
```

A PN-Counter works by maintaining two G-Counters. One G-Counter is used to track increment operations and the other
tracks decrement operations. When merging, these two G-Counters are merged with the other PN-Counter's internal counters
as described above. The value of a PN-Counter is the value of the increment counter minus the value of the decrement
counter.

A PN-Counter can be initialized with starting values contained inside two G-Counters.

```php
$incrementGCounter = new GCounter('idA', [
    'idA' => 25,
]);

$decrementGCounter = new GCounter('idA', [
    'idA' => 7,
]);

$counter = new PNCounter('idA', $incrementGCounter, $decrementGCounter);

$counter->value(); // === 18
```

### G-Set

The Grow-only Set, `CarriageDB/CRDT/Counters/GSet`, can have elements added to it and be merged with other G-Sets.

```php
$setA = new GSet();

$setA->add('apple');
$setA->add('banana');

$setA->elements(); // == ['apple', 'banana']

$setA->has('apple'); // === true
$setA->has('cherry'); // === false

$setB = new GSet();

$setB->add('cherry');

$mergeSet = $setA->merge($setB);

$mergeSet->elements(); // == ['apple', 'banana', 'cherry']

$mergeSet->has('cherry'); // === true
```

A G-Set works by maintaining a unique list of elements (for example, adding `apple` to a G-Set that already has `apple`
in it does not change the state at all). When merging, the unique lists are joined, also without creating any duplicate
elements. Because it is a grow-only set, removing elements is not allowed.

A G-Set can be initialized with starting elements.

```php
$data = [
    'apple',
    'banana',
    'cherry',
];

$set = new GSet($data);

$set->elements(); // == ['apple', 'banana', 'cherry']
```

### 2P-Set

The Two-Phase Set, `CarriageDB/CRDT/Counters/TwoPSet`, can have elements added, removed, and merged with other 2P-Sets.

```php
$setA = new TwoPSet();

$setA->add('apple');
$setA->add('banana');
$setA->add('cherry');
$setA->remove('banana');

$setA->elements(); // == ['apple', 'cherry']

$setA->has('apple'); // === true
$setA->has('banana'); // === false

$setB = new GSet();

$setB->add('banana');
$setB->add('cherry');
$setB->add('durian');
$setB->remove('durian');

$mergeSet = $setA->merge($setB);

$mergeSet->elements(); // == ['apple', 'cherry']
```

A 2P-Set works by maintaining two G-Sets. One G-Set is used to track known elements, and the other tracks elements that
have been removed. Any element inside the removal set is referred to as a tombstone. Even when an element is removed, it
still remains in the known elements set. Removing an element means _also_ adding it to the tombstone set. When merging,
these two G-Sets are merged with the other 2P-Set's internal sets as described above. A 2P-Set determines whether an
element is currently in the value set based on 1) the element existing in the known elements G-Set and 2) the element
_not_ existing in the tombstone G-Set.

A 2P-Set can be initialized with starting values contained inside two G-Sets.

```php
$knownSet = new GSet([
    'apple',
    'banana',
    'cherry',
]);

$tombstoneSet = new GSet([
    'apple',
]);

$set = new TwoPSet($knownSet, $tombstoneSet);

$set->elements(); // == ['banana', 'cherry']
```

## Development

```bash
git clone https://github.com/CarriageDB/php-crdt.git

composer install

# After making changes, ensure that all checks and tests are passing.i

composer test
```
