<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

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

        // Sometimes the cluster can be in a state where slots are
        // missing on some shards (e.g. they are being rebalanced)
        $shards = $redis->cluster->shards();
        $slots = $shards[0][1] ?? $shards[0]['slots'];

        if (empty($slots)) {
            $slots = $shards[1][1] ?? $shards[1]['slots'];
        }

        if (empty($slots)) {
            $slots = $shards[2][1] ?? $shards[2]['slots'];
        }

        [$startSlot, $endSlot] = $slots;

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
}
