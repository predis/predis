<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Predis\Client;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SpellcheckArguments;

require __DIR__ . '/../../shared.php';

// Example of FT.SPELLCHECK command usage:

// 1. Create index
$client = new Client();

$schema = [
    new TextField('text_field'),
];
$client->ftcreate('index_spellcheck', $schema);

// 2. Add dictionary with terms
$client->ftdictadd('dict', 'hello', 'help');

// 3. Perform spelling correction query
$response = $client->ftspellcheck(
    'index_spellcheck',
    'held',
    (new SpellcheckArguments())->distance(2)->terms('dict')
);

echo 'Response:' . "\n";
print_r($response);
