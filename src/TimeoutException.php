<?php

namespace Predis;

use Exception;
use Predis\Connection\NodeConnectionInterface;
use Throwable;

class TimeoutException extends Exception
{
    /**
     * @var NodeConnectionInterface
     */
    protected $connection;

    public function __construct(NodeConnectionInterface $connection = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Operation has been timed out", $code, $previous);
        $this->connection = $connection;
    }

    public function getConnection(): ?NodeConnectionInterface
    {
        return $this->connection;
    }

    public function setConnection(NodeConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}
