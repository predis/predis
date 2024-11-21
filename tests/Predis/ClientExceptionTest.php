<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use PredisTestCase;

class ClientExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage(): void
    {
        $message = 'This is a client exception.';

        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage($message);

        throw new ClientException($message);
    }

    /**
     * @group disconnected
     */
    public function testExceptionClass(): void
    {
        $exception = new ClientException();

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertInstanceOf('Predis\PredisException', $exception);
    }
}
