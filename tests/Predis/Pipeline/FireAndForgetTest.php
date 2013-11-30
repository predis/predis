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

use SplQueue;
use PredisTestCase;
use Predis\Client;
use Predis\Profile;

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
        $profile = Profile\Factory::getDefault();

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->exactly(3))->method('writeCommand');
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
        $profile = Profile\Factory::getDefault();

        $connection = $this->getMock('Predis\Connection\ReplicationConnectionInterface');
        $connection->expects($this->once())
                   ->method('switchTo')
                   ->with('master');
        $connection->expects($this->exactly(3))
                   ->method('writeCommand');
        $connection->expects($this->never())
                   ->method('readResponse');

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }
}
