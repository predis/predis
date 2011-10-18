<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Iterators;

use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;

/**
 * Streams a multibulk reply.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulkResponseSimple extends MultiBulkResponse
{
    private $_connection;

    /**
     * @param IConnectionSingle $connection Connection to Redis.
     * @param int $size Number of elements of the multibulk reply.
     */
    public function __construct(IConnectionSingle $connection, $size)
    {
        $this->_connection = $connection;
        $this->_position   = 0;
        $this->_current    = $size > 0 ? $this->getValue() : null;
        $this->_replySize  = $size;
    }

    /**
     * Handles the synchronization of the client with the Redis protocol
     * then PHP's garbage collector kicks in (e.g. then the iterator goes
     * out of the scope of a foreach).
     */
    public function __destruct()
    {
        $this->sync();
    }

    /**
     * Synchronizes the client with the queued elements that have not been
     * read from the connection by consuming the rest of the multibulk reply,
     * or simply by dropping the connection.
     *
     * @param Boolean $drop True to synchronize the client by dropping the connection.
     *                      False to synchronize the client by consuming the multibulk reply.
     */
    public function sync($drop = false)
    {
        if ($drop == true) {
            if ($this->valid()) {
                $this->_position = $this->_replySize;
                $this->_connection->disconnect();
            }
        }
        else {
            while ($this->valid()) {
                $this->next();
            }
        }
    }

    /**
     * Reads the next item of the multibulk reply from the server.
     *
     * @return mixed
     */
    protected function getValue()
    {
        return $this->_connection->read();
    }
}
