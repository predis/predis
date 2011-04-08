<?php
require_once 'SharedConfigurations.php';

// Predis ships with a KeyPrefixPreprocessor class that is used to transparently
// prefix each key before sending commands to Redis, even for complex commands
// such as SORT, ZUNIONSTORE and ZINTERSTORE. Key prefixes are useful to create
// user-level namespaces for you keyspace, thus eliminating the need for separate
// logical databases.

use Predis\Commands\Preprocessors\KeyPrefixPreprocessor;

$client = new Predis\Client();
$client->getProfile()->setPreprocessor(new KeyPrefixPreprocessor('nrk:'));

$client->mset(array('foo' => 'bar', 'lol' => 'wut'));
var_dump($client->mget('foo', 'lol'));
/*
array(2) {
  [0]=> string(3) "bar"
  [1]=> string(3) "wut"
}
*/

var_dump($client->keys('*'));
/*
array(2) {
  [0]=> string(7) "nrk:foo"
  [1]=> string(7) "nrk:lol"
}
*/
?>
