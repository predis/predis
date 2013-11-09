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

use Predis\ResponseQueued;
use Predis\Connection\ComposableConnectionInterface;
use Predis\Protocol\ResponseHandlerInterface;

/**
 * Handler for the status response type of the standard Redis wire protocol.
 * It translates certain classes of status response to PHP objects or just
 * returns the payload as a string.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseStatusHandler implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ComposableConnectionInterface $connection, $status)
    {
        switch ($status) {
            case 'OK':
                return true;

            case 'QUEUED':
                return new ResponseQueued();

            default:
                return $status;
        }
    }
}
