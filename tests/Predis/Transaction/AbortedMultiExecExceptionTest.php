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

namespace Predis\Transaction;

use Predis\Client;
use PredisTestCase;

class AbortedMultiExecExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionClass(): void
    {
        $client = new Client();
        $transaction = new MultiExec($client);
        $exception = new AbortedMultiExecException($transaction, 'ABORTED');

        $this->assertInstanceOf('Predis\PredisException', $exception);
        $this->assertSame('ABORTED', $exception->getMessage());
        $this->assertSame($transaction, $exception->getTransaction());
    }
}
