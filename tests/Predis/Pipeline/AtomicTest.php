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

use PHPUnit_Framework_TestCase as StandardTestCase;

use SplQueue;
use Predis\Client;
use Predis\Response;

/**
 *
 */
class AtomicTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection()
    {
        $queued = new Response\StatusQueued();

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->exactly(2))
                   ->method('executeCommand')
                   ->will($this->onConsecutiveCalls(true, array('PONG', 'PONG', 'PONG')));
        $connection->expects($this->exactly(3))
                   ->method('writeCommand');
        $connection->expects($this->at(3))
                   ->method('readResponse')
                   ->will($this->onConsecutiveCalls($queued, $queued, $queued));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array(true, true, true), $pipeline->execute());
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage The underlying transaction has been aborted by the server
     */
    public function testThrowsExceptionOnAbortedTransaction()
    {
        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->exactly(2))
                   ->method('executeCommand')
                   ->will($this->onConsecutiveCalls(true, null));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     * @expectedException Predis\Response\ServerException
     * @expectedExceptionMessage ERR Test error
     */
    public function testPipelineWithErrorInTransaction()
    {
        $queued = new Response\StatusQueued();
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->at(0))
                   ->method('executeCommand')
                   ->will($this->returnValue(true));
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->onConsecutiveCalls($queued, $queued, $error));
        $connection->expects($this->at(7))
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Command\TransactionDiscard'));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     * @expectedException Predis\Response\ServerException
     * @expectedExceptionMessage ERR Test error
     */
    public function testThrowsServerExceptionOnResponseErrorByDefault()
    {
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->once())
                   ->method('readResponse')
                   ->will($this->returnValue($error));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testReturnsResponseErrorWithClientExceptionsSetToFalse()
    {
        $queued = new Response\StatusQueued();
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->onConsecutiveCalls($queued, $queued, $queued));
        $connection->expects($this->at(7))
                   ->method('executeCommand')
                   ->will($this->returnValue(array('PONG', 'PONG', $error)));

        $pipeline = new Atomic(new Client($connection, array('exceptions' => false)));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array(true, true, $error), $pipeline->execute());
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage Predis\Pipeline\Atomic can be used only with connections to single nodes
     */
    public function testExecutorWithAggregatedConnection()
    {
        $connection = $this->getMock('Predis\Connection\ClusterConnectionInterface');
        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();

        $pipeline->execute();
    }
}
