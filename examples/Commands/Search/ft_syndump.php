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
use Predis\Command\Argument\Search\SchemaFields\TextField;

require __DIR__ . '/../../shared.php';

// Example of FT.SYNDUMP command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TextField('text_field'),
];
$client->ftcreate('index_syndump', $schema);

// 2. Add synonyms group with terms
$client->ftsynupdate('index_syndump', 'synonym1', null, 'term1', 'term2');

// 3. Dump terms with synonyms
$response = $client->ftsyndump('index_syndump');

echo 'Response:' . "\n";
print_r($response);
