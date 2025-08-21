<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction\Strategy;

use Predis\Connection\ConnectionInterface;
use Predis\Transaction\MultiExecState;

interface StrategyResolverInterface
{
    /**
     * Resolves the strategy associated with given connection.
     *
     * @param  ConnectionInterface $connection
     * @param  MultiExecState      $state
     * @return StrategyInterface
     */
    public function resolve(ConnectionInterface $connection, MultiExecState $state): StrategyInterface;
}
