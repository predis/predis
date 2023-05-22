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

$options = [
    'timeout' => 1.0,
    'read_write_timeout' => 1.0,

    // Relay specific options
    'cache' => true,
    // 'compression' => 'lz4',
    // 'serializer' => 'igbinary',
];

$client = new Predis\Client($single_server + $options, [
    'connections' => 'relay',
]);

// Write key to Redis
$client->set('torpedo', mt_rand());

// Retrieve key from Redis
$client->get('torpedo');

// Retrieve key from Relay (without talking to Redis)
// This key is now available to all PHP workers in this FPM pool
$client->get('torpedo');

// For debugging only:
var_export(
    $client->getConnection()->getClient()->_getKeys()
);

/*
array (
    'torpedo' => array (
        0 => array (
            'type' => 'string',
            'local-len' => 10,
            'remote-len' => 10,
            'size' => 10,
        ),
    ),
)
*/
