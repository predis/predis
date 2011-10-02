<?php

require __DIR__.'/../lib/Predis/Autoloader.php';

Predis\Autoloader::register();

$single_server = array(
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 15
);

$multiple_servers = array(
    array(
       'host'     => '127.0.0.1',
       'port'     => 6379,
       'database' => 15,
       'alias'    => 'first',
    ),
    array(
       'host'     => '127.0.0.1',
       'port'     => 6380,
       'database' => 15,
       'alias'    => 'second',
    ),
);
