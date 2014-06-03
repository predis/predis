<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use PredisTestCase;

/**
 *
 */
class StatusResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testOk()
    {
        $handler = new Handler\StatusResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $response = $handler->handle($connection, 'OK');

        $this->assertInstanceOf('Predis\Response\Status', $response);
        $this->assertEquals('OK', $response);
    }

    /**
     * @group disconnected
     */
    public function testQueued()
    {
        $handler = new Handler\StatusResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $response = $handler->handle($connection, 'QUEUED');

        $this->assertInstanceOf('Predis\Response\Status', $response);
        $this->assertEquals('QUEUED', $response);
    }

    /**
     * @group disconnected
     */
    public function testPlainString()
    {
        $handler = new Handler\StatusResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $response = $handler->handle($connection, 'Background saving started');

        $this->assertInstanceOf('Predis\Response\Status', $response);
        $this->assertEquals('Background saving started', $response);
    }
}
