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
class RequestSerializerTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSerializerIdWithNoArguments(): void
    {
        $serializer = new RequestSerializer();

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

        $result = $serializer->serialize($command);

        $this->assertSame("*1\r\n$4\r\nPING\r\n", $result);
    }

    /**
     * @group disconnected
     */
    public function testSerializerIdWithArguments(): void
    {
        $serializer = new RequestSerializer();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->once())
            ->method('getId')
            ->willReturn('SET');
        $command
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn(array('key', 'value'));

        $result = $serializer->serialize($command);

        $this->assertSame("*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n", $result);
    }

    /**
     * @group disconnected
     */
    public function testSerializerDoesNotBreakOnArgumentsWithHoles(): void
    {
        $serializer = new RequestSerializer();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->once())
            ->method('getId')
            ->willReturn('DEL');
        $command
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn(array(0 => 'key:1', 2 => 'key:2'));

        $result = $serializer->serialize($command);

        $this->assertSame("*3\r\n$3\r\nDEL\r\n$5\r\nkey:1\r\n$5\r\nkey:2\r\n", $result);
    }
}
