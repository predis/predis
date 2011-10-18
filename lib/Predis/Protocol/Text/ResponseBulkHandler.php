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

use Predis\Helpers;
use Predis\Protocol\IResponseHandler;
use Predis\Protocol\ProtocolException;
use Predis\Network\IConnectionComposable;

/**
 * Implements a response handler for bulk replies using the standard wire
 * protocol defined by Redis.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseBulkHandler implements IResponseHandler
{
    /**
     * Handles a bulk reply returned by Redis.
     *
     * @param IConnectionComposable $connection Connection to Redis.
     * @param string $lengthString Bytes size of the bulk reply.
     * @return string
     */
    public function handle(IConnectionComposable $connection, $lengthString)
    {
        $length = (int) $lengthString;

        if ($length != $lengthString) {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$length' as data length"
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
