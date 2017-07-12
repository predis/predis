<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../autoload.php';

// Example files expose Redis data without authentication so do not allow
// the files to be accessed outside localhost due to safety reasons.
// Please note that this protection is not flawless. The examples should
// always be removed before hosting Predis on a public network.
if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']){
    header('HTTP/1.0 403 Forbidden');
    die('Examples can be accessed only locally.');
}

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

$single_server = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 15,
);

$multiple_servers = array(
    array(
       'host' => '127.0.0.1',
       'port' => 6379,
       'database' => 15,
       'alias' => 'first',
    ),
    array(
       'host' => '127.0.0.1',
       'port' => 6380,
       'database' => 15,
       'alias' => 'second',
    ),
);
