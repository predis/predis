<?php

namespace Predis;

use Exception;
use Predis\Connection\NodeConnectionInterface;
use Throwable;

class TimeoutException extends CommunicationException
{
    public function __construct(NodeConnectionInterface $connection, $code = 0, Throwable $previous = null)
    {
        parent::__construct($connection, "Operation has been timed out", $code, $previous);
    }
}
