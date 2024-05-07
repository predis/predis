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

namespace Predis\Protocol;

require_once __DIR__ . '/../CommunicationExceptionTest.php';

use Exception;
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
        ?Exception $inner = null
    ): CommunicationException {
        return new ProtocolException($connection, $message, $code, $inner);
    }
}
