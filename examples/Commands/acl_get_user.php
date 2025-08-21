<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Predis\Client;

require __DIR__ . '/../shared.php';

// Example of ACL GETUSER command usage:

// 1. Set user
$client = new Client($single_server);
$response = $client->acl->setUser('Test');

// 2. Retrieve user rules:

echo 'Rules: ' . "\n";
print_r(
    $client->acl->getUser('Test')
);
