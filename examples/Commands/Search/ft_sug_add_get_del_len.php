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
use Predis\Command\Argument\Search\SugAddArguments;
use Predis\Command\Argument\Search\SugGetArguments;

require __DIR__ . '/../../shared.php';

// Example of FT.SUGADD, FT.SUGGET, FT.SUGDEL, FT.SUGLEN commands usage:

// 1. Add suggestion to key with payload
$client = new Client();

$client->ftsugadd('key', 'hello', 2, (new SugAddArguments())->payload('payload'));

echo 'Suggestions dictionary length: ' . $client->ftsuglen('key') . "\n";

// 2. Perform fuzzy search by prefix to get previous suggestion with payload
$response = $client->ftsugget('key', 'hellp', (new SugGetArguments())->fuzzy()->withPayloads());

echo 'Suggestion for "hellp" prefix:' . "\n";
print_r($response);

// 3. Removes previous suggestion from key
$client->ftsugdel('key', 'hello');
$response = $client->ftsugget('key', 'hello');

echo 'Suggestions, after removing "hello" suggestion:' . "\n";
print_r($response);
