<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use PredisTestCase;

class RedisReplicaSelectorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testEmptyReplicaByDefault(): void
    {
        $selector = new RedisReplicaSelector();

        $masterConnectionId = '127.0.0.1:6379';

        $this->assertNull($selector->getReplicaId($masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetReplicaId(): void
    {
        $selector = new RedisReplicaSelector();

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionId = '127.0.0.1:6381';

        $selector->addReplica($replicaConnectionId, $masterConnectionId);

        $this->assertNotNull($selector->getReplicaId($masterConnectionId));
        $this->assertSame($replicaConnectionId, $selector->getReplicaId($masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetARandomReplicaId(): void
    {
        $selector = new RedisReplicaSelector();

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionIds = [
            '127.0.0.1:6381',
            '127.0.0.1:6382',
            '127.0.0.1:6383',
        ];

        foreach ($replicaConnectionIds as $replicaConnectionId) {
            $selector->addReplica($replicaConnectionId, $masterConnectionId);
        }

        $this->assertOneOf($replicaConnectionIds, $selector->getReplicaId($masterConnectionId));
    }
}
