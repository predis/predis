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

if (PHP_SAPI !== 'cli') {
    exit('Example scripts are meant to be executed locally via CLI.');
}

require __DIR__ . '/../autoload.php';

function redis_version($info)
{
    if (isset($info['Server']['redis_version'])) {
        return $info['Server']['redis_version'];
    } elseif (isset($info['redis_version'])) {
        return $info['redis_version'];
    } else {
        return 'unknown version';
    }
}

$single_server = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 15,
];

$multiple_servers = [
    [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 15,
        'alias' => 'first',
    ],
    [
        'host' => '127.0.0.1',
        'port' => 6380,
        'database' => 15,
        'alias' => 'second',
    ],
];
