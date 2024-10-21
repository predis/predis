<?php

namespace Predis\Transaction\Strategy;

use Predis\Command\CommandInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Response\ResponseInterface;
use Predis\Transaction\MultiExecState;

class ReplicationConnectionStrategy extends NonClusterConnectionStrategy
{
    /**
     * @var ReplicationInterface $connection
     */
    protected $connection;

    public function __construct(ReplicationInterface $connection, MultiExecState $state)
    {
        parent::__construct($connection, $state);
    }
}
