<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Options;

use Predis\Network\IConnectionReplication;
use Predis\Network\MasterSlaveReplication;

/**
 * Option class that returns a replication connection be used by a client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientReplication extends Option
{
    /**
     * Checks if the specified value is a valid instance of IConnectionReplication.
     *
     * @param IConnectionReplication $cluster Instance of a connection cluster.
     * @return IConnectionReplication
     */
    protected function checkInstance($connection)
    {
        if (!$connection instanceof IConnectionReplication) {
            throw new \InvalidArgumentException('Instance of Predis\Network\IConnectionReplication expected');
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(IClientOptions $options, $value)
    {
        if (is_callable($value)) {
            $connection = call_user_func($value, $options);
            if (!$connection instanceof IConnectionReplication) {
                throw new \InvalidArgumentException('Instance of Predis\Network\IConnectionReplication expected');
            }
            return $connection;
        }

        if (is_string($value)) {
            if (!class_exists($value)) {
                throw new \InvalidArgumentException("Class $value does not exist");
            }
            if (!($connection = new $value()) instanceof IConnectionReplication) {
                throw new \InvalidArgumentException('Instance of Predis\Network\IConnectionReplication expected');
            }
            return $connection;
        }

        if ($value == true) {
            return $this->getDefault($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(IClientOptions $options)
    {
        return new MasterSlaveReplication();
    }
}
