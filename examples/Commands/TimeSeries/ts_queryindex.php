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

require __DIR__ . '/../../shared.php';

// Example of TS.QUERYINDEX command usage:

// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->labels('type', 'temp', 'location', 'TLV');

$createResponse = $client->tscreate('temp:TLV', $arguments);
echo "Time series with location TLV creation status: {$createResponse}\n";

$anotherArguments = (new CreateArguments())
    ->labels('type', 'temp', 'location', 'JER');

$createResponse = $client->tscreate('temp:JER', $anotherArguments);
echo "Time series with location JER creation status: {$createResponse}\n";

echo "Returns all keys with location=TLV:\n";
print_r($client->tsqueryindex('location=TLV'));
