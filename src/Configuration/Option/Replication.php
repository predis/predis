<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Configuration\OptionsInterface;
use Predis\Connection\Replication\MasterSlaveReplication;
use Predis\Connection\Replication\SentinelReplication;

/**
 * Configures an aggregate connection used for master/slave replication among
 * multiple Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Replication extends Aggregate
{
    /**
     * Returns a connection initializer from a descriptive name.
     *
     * @param OptionsInterface $options     Client options.
     * @param string           $description Identifier of a replication backend (`predis`, `sentinel`)
     *
     * @return callable
     */
    protected function getConnectionInitializerByDescription(OptionsInterface $options, $description)
    {
        if ($description === 'predis') {
            $callback = $this->getDefault($options);
        } elseif ($description === 'sentinel') {
            $callback = function ($options, $sentinels) {
                return new SentinelReplication($options->service, $sentinels, $options->connections);
            };
        } else {
            throw new \InvalidArgumentException(
                'String value for the replication option must be either `predis` or `sentinel`'
            );
        }

        return $this->getConnectionInitializer($options, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            return $this->getConnectionInitializerByDescription($options, $value);
        } else {
            return $this->getConnectionInitializer($options, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return function ($options) {
            $connection = new MasterSlaveReplication();

            if ($options->autodiscovery) {
                $connection->setConnectionFactory($options->connections);
                $connection->setAutoDiscovery(true);
            }

            return $connection;
        };
    }
}
