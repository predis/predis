<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response\Iterator;

use Predis\Connection\SingleConnectionInterface;

/**
 * Streamable multibulk response.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulkResponseSimple extends MultiBulkResponse
{
    private $connection;

    /**
     * @param SingleConnectionInterface $connection Connection to Redis.
     * @param int $size Number of elements of the multibulk reply.
     */
    public function __construct(SingleConnectionInterface $connection, $size)
    {
        $this->connection = $connection;
        $this->size = $size;
        $this->position = 0;
        $this->current  = $size > 0 ? $this->getValue() : null;
    }

    /**
     * Handles the synchronization of the client with the Redis protocol
     * then PHP's garbage collector kicks in (e.g. then the iterator goes
     * out of the scope of a foreach).
     */
    public function __destruct()
    {
        $this->drop(true);
    }

    /**
     * Drop queued elements that have not been read from the connection either
     * by consuming the rest of the multibulk response or quickly by closing the
     * underlying connection.
     *
     * @param bool $disconnect Consume the iterator or drop the connection.
     */
    public function drop($disconnect = false)
    {
        if ($disconnect) {
            if ($this->valid()) {
                $this->position = $this->size;
                $this->connection->disconnect();
            }
        } else {
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
        return $this->connection->read();
    }

    /**
     * Returns an iterator that reads the multi-bulk response as
     * list of tuples.
     *
     * @return MultiBulkResponseTuple
     */
    public function asTuple()
    {
        return new MultiBulkResponseTuple($this);
    }
}
