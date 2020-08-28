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
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\CommandInterface;

/**
 *
 */
class ProtocolProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConnectionWrite(): void
    {
        $serialized = "*1\r\n$4\r\nPING\r\n";
        $protocol = new ProtocolProcessor();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->once())
            ->method('getId')
            ->willReturn('PING');
        $command
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn(array());

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->once())
            ->method('writeBuffer')
            ->with($this->equalTo($serialized));

        $protocol->write($connection, $command);
    }

    /**
     * @group disconnected
     */
    public function testConnectionRead(): void
    {
        $protocol = new ProtocolProcessor();

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

        $this->assertEquals('OK', $protocol->read($connection));
        $this->assertEquals('ERR error message', $protocol->read($connection));
        $this->assertSame(2, $protocol->read($connection));
        $this->assertNull($protocol->read($connection));
        $this->assertNull($protocol->read($connection));
    }

    /**
     * @group disconnected
     */
    public function testIterableMultibulkSupport(): void
    {
        $protocol = new ProtocolProcessor();
        $protocol->useIterableMultibulk(true);

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->once(4))
            ->method('readLine')
            ->willReturn('*1');

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $protocol->read($connection));
    }

    /**
     * @group disconnected
     */
    public function testUnknownResponsePrefix(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Unknown response prefix: '!' [tcp://127.0.0.1:6379]");

        $protocol = new ProtocolProcessor();

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->once())
            ->method('readLine')
            ->willReturn('!');

        $protocol->read($connection);
    }
}
