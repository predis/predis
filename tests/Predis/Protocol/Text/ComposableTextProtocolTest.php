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

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class ComposableTextProtocolTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor()
    {
        $protocol = new ComposableTextProtocol();

        $this->assertInstanceOf(
            'Predis\Protocol\Text\TextRequestSerializer', $protocol->getRequestSerializer()
        );
        $this->assertInstanceOf(
            'Predis\Protocol\Text\TextResponseReader', $protocol->getResponseReader()
        );
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArguments()
    {
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

        $protocol = new ComposableTextProtocol($serializer, $reader);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
        $this->assertSame($reader, $protocol->getResponseReader());
    }

    /**
     * @group disconnected
     */
    public function testCustomRequestSerializer()
    {
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');

        $protocol = new ComposableTextProtocol();
        $protocol->setRequestSerializer($serializer);

        $this->assertSame($serializer, $protocol->getRequestSerializer());
    }

    /**
     * @group disconnected
     */
    public function testCustomResponseReader()
    {
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

        $protocol = new ComposableTextProtocol();
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
        $connection = $this->getMock('Predis\Connection\ComposableConnectionInterface');
        $serializer = $this->getMock('Predis\Protocol\RequestSerializerInterface');

        $protocol = new ComposableTextProtocol($serializer);

        $connection->expects($this->once())
                   ->method('writeBytes')
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
        $serialized = "*1\r\n$4\r\nPING\r\n";

        $connection = $this->getMock('Predis\Connection\ComposableConnectionInterface');
        $reader = $this->getMock('Predis\Protocol\ResponseReaderInterface');

        $protocol = new ComposableTextProtocol(null, $reader);

        $reader->expects($this->once())
                   ->method('read')
                   ->with($connection)
                   ->will($this->returnValue('bulk'));

        $this->assertSame('bulk', $protocol->read($connection));
    }
}
