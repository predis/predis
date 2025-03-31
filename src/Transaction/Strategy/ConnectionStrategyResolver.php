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

use InvalidArgumentException;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Transaction\MultiExecState;

class ConnectionStrategyResolver implements StrategyResolverInterface
{
    /**
     * @var array{string: string}
     */
    private $strategyMapping = [
        ClusterInterface::class => ClusterConnectionStrategy::class,
        NodeConnectionInterface::class => NodeConnectionStrategy::class,
        ReplicationInterface::class => ReplicationConnectionStrategy::class,
    ];

    /**
     * {@inheritDoc}
     * @param MultiExecState $state
     */
    public function resolve(ConnectionInterface $connection, MultiExecState $state): StrategyInterface
    {
        foreach ($this->strategyMapping as $interface => $strategy) {
            if ($connection instanceof $interface) {
                return new $strategy($connection, $state);
            }
        }

        throw new InvalidArgumentException(
            'Cannot resolve strategy associated with this connection type'
        );
    }
}
