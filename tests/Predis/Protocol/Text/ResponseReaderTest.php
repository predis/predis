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
        $handler = $this->getMockBuilder('Predis\Protocol\Text\Handler\ResponseHandlerInterface')->getMock();

        $reader = new ResponseReader();
        $reader->setHandler('+', $handler);

        $this->assertSame($handler, $reader->getHandler('+'));
    }

    /**
     * @group disconnected
     */
    public function testReadResponse()
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->at(0))
            ->method('readLine')
            ->will($this->returnValue('+OK'));
        $connection
            ->expects($this->at(1))
            ->method('readLine')
            ->will($this->returnValue('-ERR error message'));
        $connection
            ->expects($this->at(2))
            ->method('readLine')
            ->will($this->returnValue(':2'));
        $connection
            ->expects($this->at(3))
            ->method('readLine')
            ->will($this->returnValue('$-1'));
        $connection
            ->expects($this->at(4))
            ->method('readLine')
            ->will($this->returnValue('*-1'));

        $reader = new ResponseReader();

        $this->assertEquals('OK', $reader->read($connection));
        $this->assertEquals('ERR error message', $reader->read($connection));
        $this->assertSame(2, $reader->read($connection));
        $this->assertNull($reader->read($connection));
        $this->assertNull($reader->read($connection));
    }

    /**
     * @group disconnected
     */
    public function testEmptyResponseHeader()
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage('Unexpected empty reponse header [tcp://127.0.0.1:6379]');

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->once())
            ->method('readLine')
            ->will($this->returnValue(''));

        $reader = new ResponseReader();
        $reader->read($connection);
    }

    /**
     * @group disconnected
     */
    public function testUnknownResponsePrefix()
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Unknown response prefix: '!' [tcp://127.0.0.1:6379]");

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->once())
            ->method('readLine')
            ->will($this->returnValue('!'));

        $reader = new ResponseReader();
        $reader->read($connection);
    }
}
