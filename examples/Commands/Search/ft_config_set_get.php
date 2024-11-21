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

require __DIR__ . '/../../shared.php';

// Example of FT.CONFIG SET command usage:

// 1. Change default timeout setting
$client = new Client();

echo 'Response:' . "\n";

print_r(
    $client->ftconfig->set('TIMEOUT', 42)
);
print_r(
    $client->ftconfig->get('TIMEOUT')
);
