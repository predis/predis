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

namespace Predis\Protocol\Text\Handler;

use Predis\CommunicationException;
use Predis\Connection\CompositeConnectionInterface;
use Predis\Protocol\ProtocolException;

/**
 * Handler for the bulk response type in the standard Redis wire protocol.
 * It translates the payload to a string or a NULL.
 *
 * @see http://redis.io/topics/protocol
 */
class BulkResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" !== $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as a valid length for a bulk response [{$connection->getParameters()}]"
            ));
        }

        if ($length >= 0) {
            return substr($connection->readBuffer($length + 2), 0, -2);
        }

        if ($length == -1) {
            return;
        }

        CommunicationException::handle(new ProtocolException(
            $connection, "Value '$payload' is not a valid length for a bulk response [{$connection->getParameters()}]"
        ));

        return;
    }
}
