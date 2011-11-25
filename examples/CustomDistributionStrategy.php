<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require 'SharedConfigurations.php';

// Developers can customize the distribution strategy used by the client
// to distribute keys among a cluster of servers simply by creating a class
// that implements the Predis\Distribution\IDistributionStrategy interface.

use Predis\Distribution\IDistributionStrategy;
use Predis\Network\PredisCluster;

class NaiveDistributionStrategy implements IDistributionStrategy
{
    private $nodes;
    private $nodesCount;

    public function __constructor()
    {
        $this->nodes = array();
        $this->nodesCount = 0;
    }

    public function add($node, $weight = null)
    {
        $this->nodes[] = $node;
        $this->nodesCount++;
    }

    public function remove($node)
    {
        $this->nodes = array_filter($this->nodes, function($n) use($node) {
            return $n !== $node;
        });

        $this->nodesCount = count($this->nodes);
    }

    public function get($key)
    {
        $count = $this->nodesCount;
        if ($count === 0) {
            throw new RuntimeException('No connections');
        }

        return $this->nodes[$count > 1 ? abs(crc32($key) % $count) : 0];
    }

    public function generateKey($value)
    {
        return crc32($value);
    }
}

$options = array(
    'cluster' => function() {
        $distributor = new NaiveDistributionStrategy();
        return new PredisCluster($distributor);
    },
);

$client = new Predis\Client($multiple_servers, $options);

for ($i = 0; $i < 100; $i++) {
    $client->set("key:$i", str_pad($i, 4, '0', 0));
    $client->get("key:$i");
}

$server1 = $client->getClientFor('first')->info();
$server2 = $client->getClientFor('second')->info();

printf("Server '%s' has %d keys while server '%s' has %d keys.\n",
    'first', $server1['db15']['keys'], 'second', $server2['db15']['keys']
);
