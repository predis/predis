<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Client;

class CLUSTER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CLUSTER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CLUSTER';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfAddSlotsRange(): void
    {
        $arguments = ['ADDSLOTSRANGE', 1, 1000];
        $expected = ['ADDSLOTSRANGE', 1, 1000];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfDelSlotsRange(): void
    {
        $arguments = ['DELSLOTSRANGE', 1, 1000];
        $expected = ['DELSLOTSRANGE', 1, 1000];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfLinks(): void
    {
        $arguments = ['LINKS'];
        $expected = ['LINKS'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfShards(): void
    {
        $arguments = ['SHARDS'];
        $expected = ['SHARDS'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @group cluster
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testAddSlotsRangeToGivenNode(): void
    {
        $redis = $this->getClient();

        // ADDSLOTSRANGE assigns the slots to the node executing it, so only a
        // range that node already owns can be removed and added back. Slots of
        // any other shard would be taken away from their master, which turns
        // into a replica while the cluster answers with CLUSTERDOWN and MOVED.
        [$startSlot, $endSlot] = $this->getSlotsRangeOfExecutingNode($redis);

        $this->assertEquals('OK', $redis->cluster->delSlotsRange($startSlot, $endSlot));
        $this->assertEquals('OK', $redis->cluster->addSlotsRange($startSlot, $endSlot));
    }

    /**
     * @group connected
     * @group cluster
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testLinksReturnsClusterPeerLinks(): void
    {
        $redis = $this->getClient();

        $this->assertNotEmpty($redis->cluster->links());
    }

    /**
     * Returns a range of slots owned by the node executing CLUSTER commands.
     *
     * @param  Client $redis
     * @return int[]
     */
    private function getSlotsRangeOfExecutingNode(Client $redis): array
    {
        $nodes = explode("\n", trim($redis->executeRaw(['CLUSTER', 'NODES'])));

        foreach ($nodes as $node) {
            $fields = explode(' ', trim($node));

            if (strpos($fields[2], 'myself') === false) {
                continue;
            }

            // Slots follow the first eight fields, as "start-end" or a single slot
            foreach (array_slice($fields, 8) as $slots) {
                if (preg_match('/^(\d+)(?:-(\d+))?$/', $slots, $range)) {
                    return [(int) $range[1], (int) ($range[2] ?? $range[1])];
                }
            }
        }

        $this->fail('The node executing CLUSTER commands does not own any slots');
    }
}
