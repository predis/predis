<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Client;
use PredisTestCase;

class FireAndForgetTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testSwitchesToMasterWithReplicationConnection(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\Replication\ReplicationInterface')
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('switchToMaster');
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.2.0
     */
    public function testClusterExecutePipeline(): void
    {
        $pipeline = new FireAndForget($this->createClient());

        $pipeline->set('foo', 'bar');
        $pipeline->get('foo');
        $pipeline->set('bar', 'foo');
        $pipeline->get('bar');
        $pipeline->set('baz', 'baz');
        $pipeline->get('baz');

        $this->assertEmpty($pipeline->execute());
    }
}
