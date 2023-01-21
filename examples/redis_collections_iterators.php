<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/shared.php';

use Predis\Collection\Iterator;

// Starting with Redis 2.8, clients can iterate incrementally over collections
// without blocking the server like it happens when a command such as KEYS is
// executed on a Redis instance storing millions of keys. These commands are:
//
//   - SCAN (iterates over the keyspace)
//   - SSCAN (iterates over members of a set)
//   - ZSCAN (iterates over members and ranks of a sorted set)
//   - HSCAN (iterates over fields and values of an hash).

// Predis provides a specialized abstraction for each command based on standard
// SPL iterators making it possible to easily consume SCAN-based iterations in
// your PHP code.
//
// See http://redis.io/commands/scan for more details.
//

$client = new Predis\Client($single_server);

// Prepare some keys for our example
$client->del('predis:set', 'predis:zset', 'predis:hash');
for ($i = 0; $i < 5; ++$i) {
    $client->sadd('predis:set', "member:$i");
    $client->zadd('predis:zset', -$i, "member:$i");
    $client->hset('predis:hash', "field:$i", "value:$i");
}

// === Keyspace iterator based on SCAN ===
echo 'Scan the keyspace matching only our prefixed keys:', PHP_EOL;
foreach (new Iterator\Keyspace($client, 'predis:*') as $key) {
    echo " - $key", PHP_EOL;
}

/* OUTPUT
Scan the keyspace matching only our prefixed keys:
    - predis:zset
    - predis:set
    - predis:hash
*/

// === Set iterator based on SSCAN ===
echo 'Scan members of `predis:set`:', PHP_EOL;
foreach (new Iterator\SetKey($client, 'predis:set') as $member) {
    echo " - $member", PHP_EOL;
}

/* OUTPUT
Scan members of `predis:set`:
    - member:1
    - member:4
    - member:0
    - member:3
    - member:2
*/

// === Sorted set iterator based on ZSCAN ===
echo 'Scan members and ranks of `predis:zset`:', PHP_EOL;
foreach (new Iterator\SortedSetKey($client, 'predis:zset') as $member => $rank) {
    echo " - $member [rank: $rank]", PHP_EOL;
}

/* OUTPUT
Scan members and ranks of `predis:zset`:
    - member:4 [rank: -4]
    - member:3 [rank: -3]
    - member:2 [rank: -2]
    - member:1 [rank: -1]
    - member:0 [rank: 0]
*/

// === Hash iterator based on HSCAN ===
echo 'Scan fields and values of `predis:hash`:', PHP_EOL;
foreach (new Iterator\HashKey($client, 'predis:hash') as $field => $value) {
    echo " - $field => $value", PHP_EOL;
}

/* OUTPUT
Scan fields and values of `predis:hash`:
    - field:0 => value:0
    - field:1 => value:1
    - field:2 => value:2
    - field:3 => value:3
    - field:4 => value:4
*/
