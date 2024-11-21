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

class PredisExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage(): void
    {
        $message = 'Predis exception message';
        $exception = $this->getMockForAbstractClass('Predis\PredisException', [$message]);

        $this->expectException('Predis\PredisException');
        $this->expectExceptionMessage($message);

        throw $exception;
    }
}
