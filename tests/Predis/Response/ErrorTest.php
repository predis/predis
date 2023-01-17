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

class ErrorTest extends PredisTestCase
{
    public const ERR_WRONG_KEY_TYPE = 'ERR Operation against a key holding the wrong kind of value';

    /**
     * @group disconnected
     */
    public function testResponseErrorClass(): void
    {
        $error = new Error(self::ERR_WRONG_KEY_TYPE);

        $this->assertInstanceOf('Predis\Response\ErrorInterface', $error);
        $this->assertInstanceOf('Predis\Response\ResponseInterface', $error);
    }

    /**
     * @group disconnected
     */
    public function testErrorMessage(): void
    {
        $error = new Error(self::ERR_WRONG_KEY_TYPE);

        $this->assertEquals(self::ERR_WRONG_KEY_TYPE, $error->getMessage());
    }

    /**
     * @group disconnected
     */
    public function testErrorType(): void
    {
        $exception = new Error(self::ERR_WRONG_KEY_TYPE);

        $this->assertEquals('ERR', $exception->getErrorType());
    }

    /**
     * @group disconnected
     */
    public function testToString(): void
    {
        $error = new Error(self::ERR_WRONG_KEY_TYPE);

        $this->assertEquals(self::ERR_WRONG_KEY_TYPE, (string) $error);
    }
}
