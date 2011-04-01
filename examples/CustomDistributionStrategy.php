<?php
require_once 'SharedConfigurations.php';

// Developers can customize the distribution strategy used by the client 
// to distribute keys among a cluster of servers simply by creating a class 
// that implements the Predis_Distribution_IDistributionStrategy interface.

class NaiveDistributionStrategy
    implements Predis_Distribution_IDistributionStrategy {

    private $_nodes, $_nodesCount;

    public function __constructor() {
        $this->_nodes = array();
        $this->_nodesCount = 0;
    }

    public function add($node, $weight = null) {
        $this->_nodes[] = $node;
        $this->_nodesCount++;
    }

    private static function array_remove($array, $value) {
        $newArray = array();
        foreach ($array as $k => $v) {
            if ($v !== $value) {
                $newArray[] = $v;
            }
        }
        return $newArray;
    }

    public function remove($node) {
        $this->_nodes = self::array_remove($this->_nodes, $node);
        $this->_nodesCount = count($this->_nodes);
    }

    public function get($key) {
        $count = $this->_nodesCount;
        if ($count === 0) {
            throw new RuntimeException('No connections');
        }
        return $this->_nodes[$count > 1 ? abs(crc32($key) % $count) : 0];
    }

    public function generateKey($value) {
        return crc32($value);
    }
}

$options = array(
    'key_distribution' => new NaiveDistributionStrategy(),
);

$redis = new Predis_Client($multiple_servers, $options);

for ($i = 0; $i < 100; $i++) {
    $redis->set("key:$i", str_pad($i, 4, '0', 0));
    $redis->get("key:$i");
}

$server1 = $redis->getClientFor('first')->info();
$server2 = $redis->getClientFor('second')->info();

printf("Server '%s' has %d keys while server '%s' has %d keys.\n", 
    'first', $server1['db15']['keys'], 'second', $server2['db15']['keys']
);
?>
