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

use Predis\ServerException;
use Predis\Protocol\IResponseHandler;
use Predis\Network\IConnectionComposable;

/**
 * Implements a response handler for error replies using the standard wire
 * protocol defined by Redis.
 *
 * This handler throws an exception to notify the user that an error has
 * occurred on the server.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseErrorHandler implements IResponseHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(IConnectionComposable $connection, $errorMessage)
    {
        throw new ServerException($errorMessage);
    }
}
