<?php

namespace Predis\Transaction\Strategy;

use Predis\Connection\ConnectionInterface;
use Predis\Transaction\MultiExecState;

interface StrategyResolverInterface
{
    /**
     * Resolves the strategy associated with given connection.
     *
     * @param ConnectionInterface $connection
     * @param MultiExecState $state
     * @return StrategyInterface
     */
    public function resolve(ConnectionInterface $connection, MultiExecState $state): StrategyInterface;
}
