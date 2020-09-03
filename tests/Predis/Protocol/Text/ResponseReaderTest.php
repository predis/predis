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
use Predis\Protocol\Text\Handler\ResponseHandlerInterface;

/**
 *
 */
class ResponseReaderTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultHandlers(): void
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
    public function testReplaceHandler(): void
    {
        /** @var ResponseHandlerInterface */
        $handler = $this->getMockBuilder('Predis\Protocol\Text\Handler\ResponseHandlerInterface')->getMock();

        $reader = new ResponseReader();
        $reader->setHandler('+', $handler);

        $this->assertSame($handler, $reader->getHandler('+'));
    }

    /**
     * @group disconnected
     */
    public function testReadResponse(): void
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->exactly(5))
            ->method('readLine')
            ->willReturnOnConsecutiveCalls(
                '+OK',
                '-ERR error message',
                ':2',
                '$-1',
                '*-1'
            );

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
    public function testEmptyResponseHeader(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage('Unexpected empty reponse header [tcp://127.0.0.1:6379]');

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->once())
            ->method('readLine')
            ->willReturn('');

        $reader = new ResponseReader();
        $reader->read($connection);
    }

    /**
     * @group disconnected
     */
    public function testUnknownResponsePrefix(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Unknown response prefix: '!' [tcp://127.0.0.1:6379]");

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->once())
            ->method('readLine')
            ->willReturn('!');

        $reader = new ResponseReader();
        $reader->read($connection);
    }
}
