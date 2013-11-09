<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text\Handler;

use Predis\CommunicationException;
use Predis\Connection\ComposableConnectionInterface;
use Predis\Iterator\MultiBulkResponseSimple;
use Predis\Protocol\ProtocolException;

/**
 * Handler for the multibulk response type of the standard Redis wire protocol.
 * It returns multibulk responses as iterators that can stream bulk elements.
 *
 * Please note that streamable multibulk replies are not globally supported
 * by the abstractions built-in into Predis such as for transactions or
 * pipelines. Use them with care!
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StreamableMultiBulkResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ComposableConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" != $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as the length of the multibulk response"
            ));
        }

        return new MultiBulkResponseSimple($connection, $length);
    }
}
