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

// Example of TS.CREATERULE command usage:

// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->labels('type', 'temp', 'location', 'TLV');

$createResponse = $client->tscreate('temp:TLV', $arguments);
echo "Original time series creation status: {$createResponse}\n";

$createResponse = $client->tscreate('dailyAvgTemp:TLV', $arguments);
echo "Compacted time series creation status: {$createResponse}\n";

$createRuleResponse = $client->tscreaterule('temp:TLV', 'dailyAvgTemp:TLV', 'avg', 1000);
echo "Compacted rule for compacted time series creation status: {$createRuleResponse}\n";

// 2. Remove compaction rule
$deleteRuleResponse = $client->tsdeleterule('temp:TLV', 'dailyAvgTemp:TLV');
echo "Compacted rule for compacted time series deletion status: {$deleteRuleResponse}\n";
