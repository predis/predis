<?php

namespace Predis\Transaction\Strategy;

use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\ResponseInterface;
use Predis\Transaction\MultiExecState;

class NodeConnectionStrategy extends NonClusterConnectionStrategy
{
    /**
     * @var NodeConnectionInterface $connection
     */
    protected $connection;

    public function __construct(NodeConnectionInterface $connection, MultiExecState $state)
    {
        parent::__construct($connection, $state);
    }
}
