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
class CompositeProtocolProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor()
    {
        $protocol = new CompositeProtocolProcessor();

        $this->assertInstanceOf(
            'Predis\Protocol\Text\RequestSerializer', $protocol->getRequestSerializer()
        );
        $this->assertInstanceOf(
            'Predis\Protocol\Text\ResponseReader', $protocol->getResponseReader()
        );
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArguments()
    {
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor($serializer, $reader);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testCustomRequestSerializer()
    {
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();

        $protocol = new CompositeProtocolProcessor();
        $protocol->setRequestSerializer($serializer);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
    }

    /**
     * @group disconnected
     */
    public function testCustomResponseReader()
    {
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor();
        $protocol->setResponseReader($reader);

        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testConnectionWrite()
    {
        $serialized = "*1\r\n$4\r\nPING\r\n";

        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\CompositeConnectionInterface')->getMock();
        $serializer = $this->getMockBuilder('Predis\Protocol\RequestSerializerInterface')->getMock();

        $protocol = new CompositeProtocolProcessor($serializer);

        $connection->expects($this->once())
                   ->method('writeBuffer')
                   ->with($this->equalTo($serialized));

        $serializer->expects($this->once())
                   ->method('serialize')
                   ->with($command)
                   ->will($this->returnValue($serialized));

        $protocol->write($connection, $command);
    }

    /**
     * @group disconnected
     */
    public function testConnectionRead()
    {
        $connection = $this->getMockBuilder('Predis\Connection\CompositeConnectionInterface')->getMock();
        $reader = $this->getMockBuilder('Predis\Protocol\ResponseReaderInterface')->getMock();

        $protocol = new CompositeProtocolProcessor(null, $reader);

        $reader->expects($this->once())
                   ->method('read')
                   ->with($connection)
                   ->will($this->returnValue('bulk'));

        $this->assertSame('bulk', $protocol->read($connection));
    }
}
