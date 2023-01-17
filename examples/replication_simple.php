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

// Predis supports master / slave replication scenarios where write operations
// are performed on the master server and read operations are executed against
// one of the slaves. The behavior of commands or EVAL scripts can be customized
// at will. As soon as a write operation is performed the client switches to the
// master server for all the subsequent requests (either reads and writes).
//
// This example must be executed using the second Redis server configured as the
// slave of the first one (see the "SLAVEOF" command).
//

$parameters = [
    'tcp://127.0.0.1:6381?role=master&database=15',
    'tcp://127.0.0.1:6382?role=slave&database=15',
];

$options = ['replication' => 'predis'];

$client = new Predis\Client($parameters, $options);

// Read operation.
$exists = $client->exists('foo') ? 'yes' : 'no';
$current = $client->getConnection()->getCurrent()->getParameters();
echo "Does 'foo' exist on {$current->role}? $exists.", PHP_EOL;

// Write operation.
$client->set('foo', 'bar');
$current = $client->getConnection()->getCurrent()->getParameters();
echo "Now 'foo' has been set to 'bar' on {$current->role}!", PHP_EOL;

// Read operation.
$bar = $client->get('foo');
$current = $client->getConnection()->getCurrent()->getParameters();
echo "We fetched 'foo' from {$current->role} and its value is '$bar'.", PHP_EOL;

/* OUTPUT:
Does 'foo' exist on slave? yes.
Now 'foo' has been set to 'bar' on master!
We fetched 'foo' from master and its value is 'bar'.
*/
