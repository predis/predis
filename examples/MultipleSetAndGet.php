<?php
require_once 'SharedConfigurations.php';

// redis can set keys and their relative values in one go 
// using MSET, then the same values can be retrieved with 
// a single command using MGET.

$mkv = array(
    'usr:0001' => 'First user',
    'usr:0002' => 'Second user', 
    'usr:0003' => 'Third user' 
);

$redis = new Predis_Client($single_server);

$redis->mset($mkv);
$retval = $redis->mget(array_keys($mkv));

print_r($retval);

/* OUTPUT:
Array
(
    [0] => First user
    [1] => Second user
    [2] => Third user
)
*/
?>
