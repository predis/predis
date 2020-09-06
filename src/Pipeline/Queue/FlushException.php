<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline\Queue;

use Predis\Connection\ConnectionInterface;
use Throwable;

/**
 * Exception class for command queue errors during flush operations.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class FlushException extends CommandQueueException
{
    protected $connection;

    /**
     * @param ConnectionInterface $connection Connection associated to the exception
     * @param string              $message    Exception message
     * @param integer             $code       Exception code
     * @param Throwable           $previous   Previous exception for exception chaining
     */
    public function __construct(ConnectionInterface $connection, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->connection = $connection;
    }

    /**
     * Returns the connection associated to the exception.
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
