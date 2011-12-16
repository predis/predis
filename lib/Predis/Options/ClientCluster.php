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

use Predis\Network\IConnectionCluster;
use Predis\Network\PredisCluster;

/**
 * Option class that returns a connection cluster to be used by a client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientCluster extends Option
{
    /**
     * Checks if the specified value is a valid instance of IConnectionCluster.
     *
     * @param IConnectionCluster $cluster Instance of a connection cluster.
     * @return IConnectionCluster
     */
    protected function checkInstance($cluster)
    {
        if (!$cluster instanceof IConnectionCluster) {
            throw new \InvalidArgumentException('Instance of Predis\Network\IConnectionCluster expected');
        }

        return $cluster;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(IClientOptions $options, $value)
    {
        if (is_callable($value)) {
            return $this->checkInstance(call_user_func($value, $options));
        }
        $initializer = $this->getInitializer($options, $value);

        return $this->checkInstance($initializer());
    }

    /**
     * Returns an initializer for the specified FQN or type.
     *
     * @param string $fqnOrType Type of cluster or FQN of a class implementing IConnectionCluster.
     * @param IClientOptions $options Instance of the client options.
     * @return \Closure
     */
    protected function getInitializer(IClientOptions $options, $fqnOrType)
    {
        switch ($fqnOrType) {
            case 'predis':
                return function() { return new PredisCluster(); };

            default:
                // TODO: we should not even allow non-string values here.
                if (is_string($fqnOrType) && !class_exists($fqnOrType)) {
                    throw new \InvalidArgumentException("Class $fqnOrType does not exist");
                }
                return function() use($fqnOrType) {
                    return new $fqnOrType();
                };
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(IClientOptions $options)
    {
        return new PredisCluster();
    }
}
