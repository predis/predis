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
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

        $protocol = new CompositeProtocolProcessor($serializer, $reader);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testCustomRequestSerializer()
    {
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');

        $protocol = new CompositeProtocolProcessor();
        $protocol->setRequestSerializer($serializer);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
    }

    /**
     * @group disconnected
     */
    public function testCustomResponseReader()
    {
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

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

        $command = $this->getMock('Predis\Command\CommandInterface');
        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');

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
        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

        $protocol = new CompositeProtocolProcessor(null, $reader);

        $reader->expects($this->once())
                   ->method('read')
                   ->with($connection)
                   ->will($this->returnValue('bulk'));

        $this->assertSame('bulk', $protocol->read($connection));
    }
}
