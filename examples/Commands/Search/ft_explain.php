<?php

use Predis\Client;
use Predis\Command\Argument\Search\Schema;

require __DIR__ . '/../../shared.php';

// Example of FT.EXPLAIN command usage:

// 1. Create index
$client = new Client();

$schema = new Schema();
$schema->addTextField('text_field');
$client->ftcreate('index_explain', $schema);

// 2. Run query explanations
$response = $client->ftexplain('index_explain', '(foo bar)|(hello world) @date:[100 200]|@date:[500 +inf]');

echo 'Response:' . "\n";
print_r($response);
