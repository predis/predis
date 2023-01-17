<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use PredisTestCase;

class IntegerResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testInteger(): void
    {
        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\IntegerResponse();

        $this->assertSame(0, $handler->handle($connection, '0'));
        $this->assertSame(1, $handler->handle($connection, '1'));
        $this->assertSame(10, $handler->handle($connection, '10'));
        $this->assertSame(-10, $handler->handle($connection, '-10'));
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

        $handler = new Handler\IntegerResponse();

        $this->assertNull($handler->handle($connection, 'nil'));
    }

    /**
     * @group disconnected
     */
    public function testInvalid(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');
        $this->expectExceptionMessage("Cannot parse 'invalid' as a valid numeric response [tcp://127.0.0.1:6379]");

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface', 'tcp://127.0.0.1:6379');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\IntegerResponse();

        $handler->handle($connection, 'invalid');
    }
}
