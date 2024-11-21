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

// Example of FT.ALTER command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TextField('text_field'),
];
$client->ftcreate('index_alter', $schema);

echo 'Default index attributes:' . "\n";
$defaultAttributes = $client->ftinfo('index_alter');
print_r($defaultAttributes[7]);

// 2. Add additional attribute to existing index
$schema = [
    new TextField('new_field_name'),
];

$client->ftalter('index_alter', $schema);

echo 'Updated index attributes:' . "\n";
$updatedAttributes = $client->ftinfo('index_alter');
print_r($updatedAttributes[7]);
