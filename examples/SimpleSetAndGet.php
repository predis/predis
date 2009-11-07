<?php
require_once 'SharedConfigurations.php';

// simple set and get scenario

$redis = new Predis\Client(REDIS_HOST, REDIS_PORT);
$redis->select(REDIS_DB);

$redis->set('library', 'predis');
$retval = $redis->get('library');

print_r($retval);

/* OUTPUT
predis
*/
?>