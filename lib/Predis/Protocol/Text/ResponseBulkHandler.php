<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use Predis\CommunicationException;
use Predis\Connection\ComposableConnectionInterface;
use Predis\Protocol\ProtocolException;
use Predis\Protocol\ResponseHandlerInterface;

/**
 * Handler for the bulk response type of the standard Redis wire protocol.
 * It translates the payload to a string or a NULL.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseBulkHandler implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ComposableConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" !== $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as the length of the bulk response"
            ));
        }

        if ($length >= 0) {
            return substr($connection->readBytes($length + 2), 0, -2);
        }

        if ($length == -1) {
            return null;
        }
    }
}
