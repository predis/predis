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
 * Implements a response handler for multi-bulk replies using the standard
 * wire protocol defined by Redis.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseMultiBulkHandler implements IResponseHandler
{
    /**
     * Handles a multi-bulk reply returned by Redis.
     *
     * @param IConnectionComposable $connection Connection to Redis.
     * @param string $lengthString Number of items in the multi-bulk reply.
     * @return array
     */
    public function handle(IConnectionComposable $connection, $lengthString)
    {
        $length = (int) $lengthString;

        if ($length != $lengthString) {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$length' as data length"
            ));
        }

        if ($length === -1) {
            return null;
        }

        $list = array();

        if ($length > 0) {
            $handlersCache = array();
            $reader = $connection->getProtocol()->getReader();

            for ($i = 0; $i < $length; $i++) {
                $header = $connection->readLine();
                $prefix = $header[0];

                if (isset($handlersCache[$prefix])) {
                    $handler = $handlersCache[$prefix];
                }
                else {
                    $handler = $reader->getHandler($prefix);
                    $handlersCache[$prefix] = $handler;
                }

                $list[$i] = $handler->handle($connection, substr($header, 1));
            }
        }

        return $list;
    }
}
