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
class IntegerResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testInteger()
    {
        $handler = new Handler\IntegerResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $this->assertSame(0, $handler->handle($connection, '0'));
        $this->assertSame(1, $handler->handle($connection, '1'));
        $this->assertSame(10, $handler->handle($connection, '10'));
        $this->assertSame(-10, $handler->handle($connection, '-10'));
    }

    /**
     * @group disconnected
     */
    public function testNull()
    {
        $handler = new Handler\IntegerResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $this->assertNull($handler->handle($connection, 'nil'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Cannot parse 'invalid' as a valid numeric response.
     */
    public function testInvalid()
    {
        $handler = new Handler\IntegerResponse();

        $connection = $this->getMock('Predis\Connection\CompositeConnectionInterface');

        $connection->expects($this->never())->method('readLine');
        $connection->expects($this->never())->method('readBuffer');

        $handler->handle($connection, 'invalid');
    }
}
