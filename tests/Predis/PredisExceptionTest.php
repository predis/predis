<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use PredisTestCase;

/**
 *
 */
class PredisExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage(): void
    {
        $message = 'Predis exception message';
        $exception = $this->getMockForAbstractClass('Predis\PredisException', array($message));

        $this->expectException('Predis\PredisException');
        $this->expectExceptionMessage($message);

        throw $exception;
    }
}
