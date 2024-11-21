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

require __DIR__ . '/../../shared.php';

// Example of JSON.DEBUG command usage:

// 1. Set JSON object
$client = new Client();

$client->jsonset('key', '$', '{"key1":"value1","key2":"value2"}');

// 2. Dump information about json memory usage in bytes
$response = $client->jsondebug->memory('key', '$');

echo 'Response:' . "\n";
print_r($response);
