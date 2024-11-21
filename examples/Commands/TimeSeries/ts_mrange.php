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
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\MRangeArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.MRANGE command usage:

// 1. Create time series
$client = new Client();

$response = $client->tscreate('stock:A', (new CreateArguments())->labels('type', 'stock', 'name', 'A'));
echo "Time series A creation status: {$response}\n";

$response = $client->tscreate('stock:B', (new CreateArguments())->labels('type', 'stock', 'name', 'B'));
echo "Time series B creation status: {$response}\n";

// 2. Add samples into both time series
$response = $client->tsmadd('stock:A', 1000, 100, 'stock:A', 1010, 110, 'stock:A', 1020, 120);
$stringResponse = implode(', ', $response);
echo "Added samples into time series A with following timestamps: {$stringResponse}\n";

$response = $client->tsmadd('stock:B', 1000, 120, 'stock:B', 1010, 110, 'stock:B', 1020, 100);
$stringResponse = implode(', ', $response);
echo "Added samples into time series B with following timestamps: {$stringResponse}\n";

// 3. Query range across both time series filtered by "type" and grouped by max type
$mrangeArguments = (new MRangeArguments())
    ->withLabels()
    ->filter('type=stock')
    ->groupBy('type', 'max');

$response = $client->tsmrange('-', '+', $mrangeArguments);

echo "Response:\n";
print_r($response);
