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
class BulkResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testZeroLengthBulk()
    {
        $handler = new Handler\BulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->once())
                   ->method('readBuffer')
                   ->with($this->equalTo(2))
                   ->will($this->returnValue("\r\n"));

        $this->assertSame('', $handler->handle($connection, '0'));
    }

    /**
     * @group disconnected
     */
    public function testBulk()
    {
        $bulk = 'This is a bulk string.';
        $bulkLengh = strlen($bulk);

        $handler = new Handler\BulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->once())
                   ->method('readBuffer')
                   ->with($this->equalTo($bulkLengh + 2))
                   ->will($this->returnValue("$bulk\r\n"));

        $this->assertSame($bulk, $handler->handle($connection, (string) $bulkLengh));
    }

    /**
     * @group disconnected
     */
    public function testNull()
    {
        $handler = new Handler\BulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $this->assertNull($handler->handle($connection, '-1'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Cannot parse 'invalid' as a valid length for a bulk response.
     */
    public function testInvalidLength()
    {
        $handler = new Handler\BulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $handler->handle($connection, 'invalid');
    }
}
