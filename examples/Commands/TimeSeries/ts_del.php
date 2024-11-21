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
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.GET command usage:
// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->retentionMsecs(60000)
    ->duplicatePolicy(CommonArguments::POLICY_MAX)
    ->labels('sensor_id', 2, 'area_id', 32);

$client->tscreate('temperature:2:32', $arguments);

// 2. Add samples into time series
$client->tsadd('temperature:2:32', 123123123123, 27);
$client->tsadd('temperature:2:32', 123123123124, 28);
$client->tsadd('temperature:2:32', 123123123125, 29);

$response = $client->tsget('temperature:2:32');

echo "Sample with highest timestamp - {$response[0]} and value {$response[1]}\n";

// 3. Removes 2 samples with the highest timestamps
$response = $client->tsdel('temperature:2:32', 123123123124, 123123123125);

echo "Removed {$response} samples from timeseries.\n";

// 3. Retrieve a timestamp with the highest timestamp
$response = $client->tsget('temperature:2:32');

echo "New sample with highest timestamp - {$response[0]} and value {$response[1]}";
