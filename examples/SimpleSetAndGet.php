<?php

require 'SharedConfigurations.php';

// simple set and get scenario

$redis = new Predis\Client($single_server);

$redis->set('library', 'predis');
$retval = $redis->get('library');

var_dump($retval);

/* OUTPUT
string(6) "predis"
*/
