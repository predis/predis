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

namespace Predis\Response;

use PredisTestCase;

class ServerExceptionTest extends PredisTestCase
{
    public const ERR_WRONG_KEY_TYPE = 'ERR Operation against a key holding the wrong kind of value';

    /**
     * @group disconnected
     */
    public function testExceptionMessage(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage(self::ERR_WRONG_KEY_TYPE);

        throw new ServerException(self::ERR_WRONG_KEY_TYPE);
    }

    /**
     * @group disconnected
     */
    public function testExceptionClass(): void
    {
        $exception = new ServerException(self::ERR_WRONG_KEY_TYPE);

        $this->assertInstanceOf('Predis\Response\ServerException', $exception);
        $this->assertInstanceOf('Predis\Response\ErrorInterface', $exception);
        $this->assertInstanceOf('Predis\Response\ResponseInterface', $exception);
        $this->assertInstanceOf('Predis\PredisException', $exception);
    }

    /**
     * @group disconnected
     */
    public function testErrorType(): void
    {
        $exception = new ServerException(self::ERR_WRONG_KEY_TYPE);

        $this->assertEquals('ERR', $exception->getErrorType());
    }

    /**
     * @group disconnected
     */
    public function testToErrorResponse(): void
    {
        $exception = new ServerException(self::ERR_WRONG_KEY_TYPE);
        $error = $exception->toErrorResponse();

        $this->assertInstanceOf('Predis\Response\Error', $error);

        $this->assertEquals($exception->getMessage(), $error->getMessage());
        $this->assertEquals($exception->getErrorType(), $error->getErrorType());
    }
}
