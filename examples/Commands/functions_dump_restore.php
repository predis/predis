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

use Predis\Client;

require __DIR__ . '/../shared.php';

// Example of FUNCTIONS DUMP, RESTORE command usage:

// 1. Load function
$client = new Client($single_server);
$response = $client->function->load(
    "#!lua name=mylib \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
);

echo "Library {$response}, was successfully loaded\n";

// 2. Return the serialized payload of loaded libraries.
$dumpResponse = $client->function->dump();

echo "Serialized value: {$dumpResponse}\n";

// 3. Flush all functions
$response = $client->function->flush();
$status = ($response == 'OK') ? 'Functions flushed' : 'Functions was not flushed';

echo $status . "\n";

// 4. Restore function from dumped payload
$response = $client->function->restore($dumpResponse);
$status = ($response == 'OK') ? 'Function was restored' : 'Function was not restored';

echo $status . "\n";

// 5. Check function list again
$response = $client->function->list('mylib');

echo 'Restored function:' . "\n";
echo print_r($response);

// 6. Flush functions
$client->function->flush();
