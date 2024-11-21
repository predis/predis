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

// Example of FT.EXPLAIN command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TextField('text_field'),
];
$client->ftcreate('index_explain', $schema);

// 2. Run query explanations
$response = $client->ftexplain('index_explain', '(foo bar)|(hello world) @date:[100 200]|@date:[500 +inf]');

echo 'Response:' . "\n";
print_r($response);
