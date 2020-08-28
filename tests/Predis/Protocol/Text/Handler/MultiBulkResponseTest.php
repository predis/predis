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
class MultiBulkResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testMultiBulk(): void
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->once())
            ->method('getProtocol')
            ->willReturn(
                new CompositeProtocolProcessor()
            );
        $connection
            ->expects($this->exactly(2))
            ->method('readLine')
            ->willReturnOnConsecutiveCalls(
                '$3',
                '$3'
            );
        $connection
            ->expects($this->exactly(2))
            ->method('readBuffer')
            ->willReturnOnConsecutiveCalls(
                "foo\r\n",
                "bar\r\n"
            );

        $handler = new Handler\MultiBulkResponse();

        $this->assertSame(array('foo', 'bar'), $handler->handle($connection, '2'));
    }

    /**
     * @group disconnected
     */
    public function testNull(): void
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\MultiBulkResponse();

        $this->assertNull($handler->handle($connection, '-1'));
    }

    /**
     * @group disconnected
     */
    public function testInvalid(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Cannot parse 'invalid' as a valid length of a multi-bulk response [tcp://127.0.0.1:6379]");

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\MultiBulkResponse();

        $handler->handle($connection, 'invalid');
    }
}
