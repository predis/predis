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
use Predis\Command\Argument\TimeSeries\MGetArguments;

require __DIR__ . '/../../shared.php';

// Example of TS.MGET command usage:

// 1. Create time series
$client = new Client();

$arguments = (new CreateArguments())
    ->retentionMsecs(60000)
    ->duplicatePolicy(CommonArguments::POLICY_MAX)
    ->labels('type', 'temp', 'sensor_id', 2, 'area_id', 32);

$client->tscreate('temperature:2:32', $arguments);
$client->tscreate('temperature:2:33', $arguments);

// 2. Add samples into time series
$client->tsadd('temperature:2:32', 123123123123, 27);
$client->tsadd('temperature:2:33', 123123123124, 27);

// 3. Get sample from multiple time series matching given filter expression, with selected labels only
$response = $client->tsmget((new MGetArguments())->selectedLabels('type'), 'type=temp');

echo "Sample from time series, with label = 'type':\n";
print_r($response);
