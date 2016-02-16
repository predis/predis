<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Client;
use PredisTestCase;

/**
 *
 */
class FireAndForgetTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(3))->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testSwitchesToMasterWithReplicationConnection()
    {
        $connection = $this->getMock('Predis\Connection\Aggregate\ReplicationInterface');
        $connection->expects($this->once())
                   ->method('switchTo')
                   ->with('master');
        $connection->expects($this->exactly(3))
                   ->method('writeRequest');
        $connection->expects($this->never())
                   ->method('readResponse');

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }
}
