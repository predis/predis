<?php
require_once 'SharedConfigurations.php';

// simple set and get scenario

$redis = new Predis\Client($single_server);

$redis->set('library', 'predis');
$retval = $redis->get('library');

print_r($retval);

/* OUTPUT
predis
*/
?>
