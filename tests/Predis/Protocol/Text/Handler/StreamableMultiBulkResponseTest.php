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
class StreamableMultiBulkResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testOk(): void
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\StreamableMultiBulkResponse();

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $handler->handle($connection, '1'));
    }

    /**
     * @group disconnected
     */
    public function testInvalid(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Cannot parse 'invalid' as a valid length for a multi-bulk response [tcp://127.0.0.1:6379]");

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\StreamableMultiBulkResponse();

        $handler->handle($connection, 'invalid');
    }
}
