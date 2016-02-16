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
class ResponseReaderTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultHandlers()
    {
        $reader = new ResponseReader();

        $this->assertInstanceOf('Predis\Protocol\Text\Handler\StatusResponse', $reader->getHandler('+'));
        $this->assertInstanceOf('Predis\Protocol\Text\Handler\ErrorResponse', $reader->getHandler('-'));
        $this->assertInstanceOf('Predis\Protocol\Text\Handler\IntegerResponse', $reader->getHandler(':'));
        $this->assertInstanceOf('Predis\Protocol\Text\Handler\BulkResponse', $reader->getHandler('$'));
        $this->assertInstanceOf('Predis\Protocol\Text\Handler\MultiBulkResponse', $reader->getHandler('*'));

        $this->assertNull($reader->getHandler('!'));
    }

    /**
     * @group disconnected
     */
    public function testReplaceHandler()
    {
        $handler = $this->getMock('Predis\Protocol\Text\Handler\ResponseHandlerInterface');

        $reader = new ResponseReader();
        $reader->setHandler('+', $handler);

        $this->assertSame($handler, $reader->getHandler('+'));
    }

    /**
     * @group disconnected
     */
    public function testReadResponse()
    {
        $reader = new ResponseReader();

        $protocol = new CompositeProtocolProcessor();
        $protocol->setResponseReader($reader);

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

        $this->assertEquals('OK', $reader->read($connection));
        $this->assertEquals('ERR error message', $reader->read($connection));
        $this->assertSame(2, $reader->read($connection));
        $this->assertNull($reader->read($connection));
        $this->assertNull($reader->read($connection));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Unexpected empty reponse header.
     */
    public function testEmptyResponseHeader()
    {
        $reader = new ResponseReader();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once())
                   ->method('readLine')
                   ->will($this->returnValue(''));

        $reader->read($connection);
    }
    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Unknown response prefix: '!'.
     */
    public function testUnknownResponsePrefix()
    {
        $reader = new ResponseReader();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->once())
                   ->method('readLine')
                   ->will($this->returnValue('!'));

        $reader->read($connection);
    }
}
