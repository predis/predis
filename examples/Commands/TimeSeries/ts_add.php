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
use Predis\Command\Argument\TimeSeries\AddArguments;
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.ADD command usage:

// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->retentionMsecs(60000)
    ->duplicatePolicy(CommonArguments::POLICY_MAX)
    ->labels('sensor_id', 2, 'area_id', 32);

$client->tscreate('temperature:2:32', $arguments);

// 2. Add sample into newly created time series
$addArguments = (new AddArguments())
    ->retentionMsecs(31536000000);

$response = $client->tsadd('temperature:2:32', 123123123123, 27, $addArguments);

echo "Timeseries was added with timestamp: {$response}";
