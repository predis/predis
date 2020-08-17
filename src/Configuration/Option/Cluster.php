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

use Predis\Cluster\RedisStrategy;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\Cluster\PredisCluster;
use Predis\Connection\Cluster\RedisCluster;

/**
 * Configures an aggregate connection used for clustering
 * multiple Redis nodes using various implementations with
 * different algorithms or strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Cluster extends Aggregate
{
    /**
     * Returns a connection initializer from a descriptive name.
     *
     * @param OptionsInterface $options     Client options.
     * @param string           $description Identifier of a cluster backend (`predis`, `redis`)
     *
     * @return callable
     */
    protected function getConnectionInitializerByDescription(OptionsInterface $options, $description)
    {
        if ($description === 'predis') {
            $callback = $this->getDefault($options);
        } elseif ($description === 'redis') {
            $callback = function ($options) {
                return new RedisCluster($options->connections, new RedisStrategy($options->crc16));
            };
        } else {
            throw new \InvalidArgumentException(
                'String value for the cluster option must be either `predis` or `redis`'
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
            return new PredisCluster();
        };
    }
}
