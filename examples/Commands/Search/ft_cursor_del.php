<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Predis\Client;
use Predis\Command\Argument\Search\AggregateArguments;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TextField;

require __DIR__ . '/../../shared.php';

// Example of FT.CURSOR DEL command usage

// 1. Create index
$client = new Client();

$ftCreateArguments = (new CreateArguments())->prefix(['user:']);
$schema = [
    new TextField('name'),
    new TextField('country'),
    new NumericField('dob', '', AbstractField::SORTABLE),
];

$client->ftcreate('index_cursor_del', $schema, $ftCreateArguments);

// 2. Add documents
$client->hset('user:0', 'name', 'Vlad', 'country', 'Ukraine', 'dob', 813801600);
$client->hset('user:1', 'name', 'Vlad', 'country', 'Israel', 'dob', 782265600);
$client->hset('user:2', 'name', 'Vlad', 'country', 'Ukraine', 'dob', 813801600);

// 3. Execute aggregation query
$ftAggregateArguments = (new AggregateArguments())
    ->apply('year(@dob)', 'birth')
    ->groupBy('@country', '@birth')
    ->reduce('COUNT', true, 'country_birth_Vlad_count')
    ->sortBy(0, '@birth', 'DESC')
    ->withCursor(1);

[$_, $cursor] = $client->ftaggregate('index_cursor_del', '@name: "Vlad"', $ftAggregateArguments);

// 4. Explicitly remove returned cursor
$client->ftcursor->del('index_cursor_del', $cursor);

echo "Cursor with cursorId - {$cursor} was removed";
