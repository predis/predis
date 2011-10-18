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
use Predis\Iterators\MultiBulkResponseSimple;

/**
 * Implements a response handler for iterable multi-bulk replies using the
 * standard wire protocol defined by Redis.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseMultiBulkStreamHandler implements IResponseHandler
{
    /**
     * Handles a multi-bulk reply returned by Redis in a streamable fashion.
     *
     * @param IConnectionComposable $connection Connection to Redis.
     * @param string $lengthString Number of items in the multi-bulk reply.
     * @return MultiBulkResponseSimple
     */
    public function handle(IConnectionComposable $connection, $lengthString)
    {
        $length = (int) $lengthString;

        if ($length != $lengthString) {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$length' as data length"
            ));
        }

        return new MultiBulkResponseSimple($connection, $length);
    }
}
