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

use Predis\Client;

require __DIR__ . '/../shared.php';

// Example of FCALL_RO command usage:

// 1. Set key-value pair
$client = new Client($single_server);
$client->set('foo', 'bar');

echo "Set key 'foo' with value 'bar'\n";

// 2. Load redis function with 'no-writes' flag
$client->function->load(
    "#!lua name=mylib
                redis.register_function{
                    function_name='myfunc',
                    callback=function(keys, args) return redis.call('GET', keys[1]) end,
                    flags={'no-writes'}
                }"
);

echo 'Loaded custom function that perform GET command against provided key.' . "\n";

// 3. Call function above with given key
$response = $client->fcall_ro('myfunc', ['foo']);

echo "Function returned value against provided key 'foo' is '{$response}'";

// 4. Delete test library
$client->function->delete('mylib');
