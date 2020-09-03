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
use Predis\Connection\CompositeConnectionInterface;
use Predis\Protocol\RequestSerializerInterface;
use Predis\Protocol\ResponseReaderInterface;

/**
 *
 */
class CompositeProtocolProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor(): void
    {
        $protocol = new CompositeProtocolProcessor();

        $this->assertInstanceOf('Predis\Protocol\Text\RequestSerializer', $protocol->getRequestSerializer());
        $this->assertInstanceOf('Predis\Protocol\Text\ResponseReader', $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArguments(): void
    {
        /** @var RequestSerializerInterface */
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();
        /** @var ResponseReaderInterface */
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor($serializer, $reader);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testCustomRequestSerializer(): void
    {
        /** @var RequestSerializerInterface */
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();

        $protocol = new CompositeProtocolProcessor();
        $protocol->setRequestSerializer($serializer);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
    }

    /**
     * @group disconnected
     */
    public function testCustomResponseReader(): void
    {
        /** @var ResponseReaderInterface */
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor();
        $protocol->setResponseReader($reader);

        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testConnectionWrite(): void
    {
        $serialized = "*1\r\n$4\r\nPING\r\n";

        /** @var CommandInterface */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        /** @var CompositeConnectionInterface|MockObject */
        $connection = $this->getMockBuilder('Predis\Connection\CompositeConnectionInterface')->getMock();
        /** @var RequestSerializerInterface|MockObject */
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();

        $protocol = new CompositeProtocolProcessor($serializer);

        $connection
            ->expects($this->once())
            ->method('writeBuffer')
            ->with($this->equalTo($serialized));

        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($command)
            ->willReturn($serialized);

        $protocol->write($connection, $command);
    }

    /**
     * @group disconnected
     */
    public function testConnectionRead(): void
    {
        /** @var CompositeConnectionInterface */
        $connection = $this->getMockBuilder('Predis\Connection\CompositeConnectionInterface')->getMock();
        /** @var ResponseReaderInterface|MockObject */
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor(null, $reader);

        $reader
            ->expects($this->once())
            ->method('read')
            ->with($connection)
            ->willReturn('bulk');

        $this->assertSame('bulk', $protocol->read($connection));
    }
}
