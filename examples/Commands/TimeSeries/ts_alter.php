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
use Predis\Command\Argument\TimeSeries\AlterArguments;
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.ALTER command usage:

// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->retentionMsecs(60000)
    ->duplicatePolicy(CommonArguments::POLICY_MAX)
    ->labels('sensor_id', 2, 'area_id', 32);

$response = $client->tscreate('temperature:2:32', $arguments);

echo "Time series creation status: {$response}\n";

// 2. Update Duplicate policy for time series above
$arguments = (new AlterArguments())
    ->duplicatePolicy(CommonArguments::POLICY_FIRST);

$response = $client->tsalter('temperature:2:32', $arguments);
$output = ($response == 'OK') ? 'Duplicate policy was successfully updated' : $response;

echo $output;
