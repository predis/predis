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

use \PHPUnit_Framework_TestCase as StandardTestCase;

use SplQueue;
use Predis\ResponseError;
use Predis\Profile\ServerProfile;

/**
 *
 */
class StandardExecutorTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testExecutorWithSingleConnection()
    {
        $executor = new StandardExecutor();
        $pipeline = $this->getCommandsQueue();

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->exactly(3))
                   ->method('writeCommand');
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->returnValue('PONG'));

        $replies = $executor->execute($connection, $pipeline);

        $this->assertTrue($pipeline->isEmpty());
        $this->assertSame(array('PONG', 'PONG', 'PONG'), $replies);
    }

    /**
     * @group disconnected
     */
    public function testExecutorWithReplicationConnection()
    {
        $executor = new StandardExecutor();
        $pipeline = $this->getCommandsQueue();

        $connection = $this->getMock('Predis\Connection\ReplicationConnectionInterface');
        $connection->expects($this->once())
                   ->method('switchTo')
                   ->with('master');
        $connection->expects($this->exactly(3))
                   ->method('writeCommand');
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->returnValue('PONG'));

        $replies = $executor->execute($connection, $pipeline);

        $this->assertTrue($pipeline->isEmpty());
        $this->assertSame(array('PONG', 'PONG', 'PONG'), $replies);
    }

    /**
     * @group disconnected
     * @expectedException Predis\ServerException
     * @expectedExceptionMessage ERR Test error
     */
    public function testExecutorCanThrowExceptions()
    {
        $executor = new StandardExecutor(true);
        $pipeline = $this->getCommandsQueue();
        $error = new ResponseError('ERR Test error');

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->once())
                   ->method('readResponse')
                   ->will($this->returnValue($error));

        $executor->execute($connection, $pipeline);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a list of queued command instances.
     *
     * @return SplQueue
     */
    protected function getCommandsQueue()
    {
        $profile = ServerProfile::getDevelopment();

        $pipeline = new SplQueue();
        $pipeline->enqueue($profile->createCommand('ping'));
        $pipeline->enqueue($profile->createCommand('ping'));
        $pipeline->enqueue($profile->createCommand('ping'));

        return $pipeline;
    }
}
