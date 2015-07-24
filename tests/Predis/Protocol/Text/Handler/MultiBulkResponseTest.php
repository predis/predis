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
class MultiBulkResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testMultiBulk()
    {
        $handler = new Handler\MultiBulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once())
                   ->method('getProtocol')
                   ->will($this->returnValue(new CompositeProtocolProcessor()));

        $connection->expects($this->at(1))
                   ->method('readLine')
                   ->will($this->returnValue('$3'));

        $connection->expects($this->at(2))
                   ->method('readBuffer')
                   ->will($this->returnValue("foo\r\n"));

        $connection->expects($this->at(3))
                   ->method('readLine')
                   ->will($this->returnValue('$3'));

        $connection->expects($this->at(4))
                   ->method('readBuffer')
                   ->will($this->returnValue("bar\r\n"));

        $this->assertSame(array('foo', 'bar'), $handler->handle($connection, '2'));
    }

    /**
     * @group disconnected
     */
    public function testNull()
    {
        $handler = new Handler\MultiBulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $this->assertNull($handler->handle($connection, '-1'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Cannot parse 'invalid' as a valid length of a multi-bulk response.
     */
    public function testInvalid()
    {
        $handler = new Handler\MultiBulkResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $handler->handle($connection, 'invalid');
    }
}
