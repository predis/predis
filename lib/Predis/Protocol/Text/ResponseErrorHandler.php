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

use Predis\ResponseError;
use Predis\Connection\ComposableConnectionInterface;
use Predis\Protocol\ResponseHandlerInterface;

/**
 * Handler for the error response type of the standard Redis wire protocol.
 * It translates the payload to a complex response object for Predis.
 *
 * This handler returns a reply object to notify the user that an error has
 * occurred on the server.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseErrorHandler implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ComposableConnectionInterface $connection, $payload)
    {
        return new ResponseError($payload);
    }
}
