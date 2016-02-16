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
class ProtocolProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConnectionWrite()
    {
        $serialized = "*1\r\n$4\r\nPING\r\n";
        $protocol = new ProtocolProcessor();

        $command = $this->getMock('Predis\Command\CommandInterface');

        $command->expects($this->once())
                ->method('getId')
                ->will($this->returnValue('PING'));

        $command->expects($this->once())
                ->method('getArguments')
                ->will($this->returnValue(array()));

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once())
                   ->method('writeBuffer')
                   ->with($this->equalTo($serialized));

        $protocol->write($connection, $command);
    }

    /**
     * @group disconnected
     */
    public function testConnectionRead()
    {
        $protocol = new ProtocolProcessor();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->at(0))
                   ->method('readLine')
                   ->will($this->returnValue('+OK'));

        $connection->expects($this->at(1))
                   ->method('readLine')
                   ->will($this->returnValue('-ERR error message'));

        $connection->expects($this->at(2))
                   ->method('readLine')
                   ->will($this->returnValue(':2'));

        $connection->expects($this->at(3))
                   ->method('readLine')
                   ->will($this->returnValue('$-1'));

        $connection->expects($this->at(4))
                   ->method('readLine')
                   ->will($this->returnValue('*-1'));

        $this->assertEquals('OK', $protocol->read($connection));
        $this->assertEquals('ERR error message', $protocol->read($connection));
        $this->assertSame(2, $protocol->read($connection));
        $this->assertNull($protocol->read($connection));
        $this->assertNull($protocol->read($connection));
    }

    /**
     * @group disconnected
     */
    public function testIterableMultibulkSupport()
    {
        $protocol = new ProtocolProcessor();
        $protocol->useIterableMultibulk(true);

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once(4))
                   ->method('readLine')
                   ->will($this->returnValue('*1'));

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $protocol->read($connection));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Unknown response prefix: '!'.
     */
    public function testUnknownResponsePrefix()
    {
        $protocol = new ProtocolProcessor();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once())
                   ->method('readLine')
                   ->will($this->returnValue('!'));

        $protocol->read($connection);
    }
}
