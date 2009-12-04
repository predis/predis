<?php
require_once 'SharedConfigurations.php';

// when you have a whole set of consecutive commands to send to 
// a redis server, you can use a pipeline to improve performances.

$redis = Predis_Client::create($configurations);

$pipe = $redis->pipeline();
$pipe->ping();
$pipe->flushdb();
$pipe->incrby('counter', 10);
$pipe->incrby('counter', 30);
$pipe->exists('counter');
$pipe->get('counter');
$pipe->mget('does_not_exist', 'counter');
$replies = $pipe->execute();

print_r($replies);

/* OUTPUT:
Array
(
    [0] => 1
    [1] => 1
    [2] => 10
    [3] => 40
    [4] => 1
    [5] => 40
    [6] => Array
        (
            [0] => 
            [1] => 40
        )

)
*/
?>