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

// Example of FT.SYNUPDATE command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TextField('text_field'),
];
$client->ftcreate('index_synupdate', $schema);

// 2. Add synonyms into synonym group
$response = $client->ftsynupdate('index_synupdate', 'synonym1', null, 'term1', 'term2');

echo 'Response:' . "\n";
print_r($response);
