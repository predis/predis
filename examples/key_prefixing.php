<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/shared.php';

// Predis can prefix keys found in commands arguments before sending commands to
// Redis, even for complex commands such as SORT, ZUNIONSTORE and ZINTERSTORE.
// Prefixing keys can be useful to create user-level namespaces for you keyspace
// thus reducing the need for separate logical databases in certain scenarios.

$client = new Predis\Client($single_server, ['prefix' => 'nrk:']);

$client->mset(['foo' => 'bar', 'lol' => 'wut']);
var_export($client->mget('foo', 'lol'));
/*
array (
    0 => 'bar',
    1 => 'wut',
)
*/

var_export($client->keys('*'));
/*
array (
    0 => 'nrk:foo',
    1 => 'nrk:lol',
)
*/
