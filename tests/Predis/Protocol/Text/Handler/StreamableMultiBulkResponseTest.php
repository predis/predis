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

use PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class StreamableMultiBulkResponseTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testOk()
    {
        $handler = new Handler\StreamableMultiBulkResponse();

        $connection = $this->getMock('Predis\Connection\ComposableConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBytes');

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $handler->handle($connection, '1'));
    }

    /**
     * @group disconnected
     * @expectedException Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Cannot parse 'invalid' as the length of the multibulk response
     */
    public function testInvalid()
    {
        $handler = new Handler\StreamableMultiBulkResponse();

        $connection = $this->getMock('Predis\Connection\ComposableConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBytes');

        $handler->handle($connection, 'invalid');
    }
}
