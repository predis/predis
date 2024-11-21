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
use Predis\Command\Argument\TimeSeries\RangeArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.RANGE command usage:

// 1. Create time series
$client = new Client();

$createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
$createResponse = $client->tscreate('temp:TLV', $createArguments);

echo "Time series creation status: {$createResponse}\n";

// 2. Add samples into time series
$maddResponse = $client->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 9999, 'temp:TLV', 1030, 40);
$stringResponse = implode(', ', $maddResponse);

echo "Samples was added with following timestamps: {$stringResponse}\n";

// 3. Query samples by values in the given range
$rangeArguments = (new RangeArguments())->filterByValue(-100, 100);
$rangeResponse = $client->tsrange('temp:TLV', '-', '+', $rangeArguments);

echo "Samples with temperature in range -100 to 100 degrees:\n";
print_r($rangeResponse);
