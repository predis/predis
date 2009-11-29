<?php
require_once 'SharedConfigurations.php';

// simple set and get scenario

$redis = Predis\Client::create($configurations);

$redis->set('library', 'predis');
$retval = $redis->get('library');

print_r($retval);

/* OUTPUT
predis
*/
?>