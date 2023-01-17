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

class ErrorResponseTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testOk(): void
    {
        $message = 'ERR Operation against a key holding the wrong kind of value';

        $connection = $this->getMockConnectionOfType('Predis\Connection\CompositeConnectionInterface');
        $connection
            ->expects($this->never())
            ->method('readLine');
        $connection
            ->expects($this->never())
            ->method('readBuffer');

        $handler = new Handler\ErrorResponse();
        $response = $handler->handle($connection, $message);

        $this->assertInstanceOf('Predis\Response\Error', $response);
        $this->assertSame($message, $response->getMessage());
    }
}
