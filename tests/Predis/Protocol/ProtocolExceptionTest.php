<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol;

require_once __DIR__.'/../CommunicationExceptionTest.php';

use Predis\CommunicationException;
use Predis\CommunicationExceptionTest;
use Predis\Connection\NodeConnectionInterface;

class ProtocolExceptionTest extends CommunicationExceptionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getException(
        NodeConnectionInterface $connection,
        string $message,
        int $code = 0,
        \Exception $inner = null
    ): CommunicationException {
        return new ProtocolException($connection, $message, $code, $inner);
    }
}
