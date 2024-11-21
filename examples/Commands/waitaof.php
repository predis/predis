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

// Example of WAITAOF command usage:

// 1. Enable appendonly mode if it's not (command works only in appendonly mode)
$client = new Client($single_server);
$info = $client->info();
$enabled = false;

if ($info['Persistence']['aof_enabled'] === '0') {
    $client->config('set', 'appendonly', 'yes');
    $enabled = true;
}

// 2. Set key value pair
$response = $client->set('foo', 'bar');
echo "Key-value pair set status: {$response}\n";

// 3. Run WAITAOF command to make sure that all previous writes was fsynced
$response = $client->waitaof(1, 0, 0);

echo "Quantity of local instances that was fsynced - {$response[0]}, quantity of replicas - {$response[1]}";

// 4. Disable appendonly mode if it was enabled during script execution
if ($enabled) {
    $client->config('set', 'appendonly', 'no');
}
