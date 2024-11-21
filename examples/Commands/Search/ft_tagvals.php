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
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TagField;

require __DIR__ . '/../../shared.php';

// Example of FT.TAGVALS command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TagField('tag_field'),
];
$client->ftcreate('index_tagvals', $schema, (new CreateArguments())->prefix(['prefix:']));

// 2. Add indexed tags
$client->hset('prefix:1', 'tag_field', 'Hello, World');
$client->hset('prefix:2', 'tag_field', 'Hey, World');

// 3. Unique tags value query
$response = $client->fttagvals('index_tagvals', 'tag_field');

echo 'Response:' . "\n";
print_r($response);
