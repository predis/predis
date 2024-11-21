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

require __DIR__ . '/../shared.php';

// Example of ACL DRYRUN command usage:

// 1. Set user with permissions to call only 'SET' command.
$client = new Client($single_server);
$response = $client->acl->setUser('Test_dry', '+SET', '~*');
$created = ($response == 'OK') ? 'Yes' : 'No';

echo "User with username 'Test' was created: {$created}. Permissions only to use SET command\n";

// 2. Dry run 'SET' command under 'Test_dry' user
$response = $client->acl->dryRun('Test_dry', 'SET', 'foo', 'bar');

echo 'Dry run "SET" command.' . "\n";
echo 'Response: ' . $response . "\n";

// 3. Dry run 'GET' command under 'Test_dry' user
$response = $client->acl->dryRun('Test_dry', 'GET', 'foo');

echo 'Dry run "GET" command.' . "\n";
echo 'Response: ' . $response;
