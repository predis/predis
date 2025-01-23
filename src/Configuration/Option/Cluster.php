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

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Cluster\RedisStrategy;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\Cluster\PredisCluster;
use Predis\Connection\Cluster\RedisCluster;

/**
 * Configures an aggregate connection used for clustering
 * multiple Redis nodes using various implementations with
 * different algorithms or strategies.
 */
class Cluster extends Aggregate
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            $value = $this->getConnectionInitializerByString($options, $value);
        }

        if (is_callable($value)) {
            return $this->getConnectionInitializer($options, $value);
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s expects either a string or a callable value, %s given',
                static::class,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    /**
     * Returns a connection initializer from a descriptive name.
     *
     * @param OptionsInterface $options     Client options
     * @param string           $description Identifier of a replication backend (`predis`, `sentinel`)
     *
     * @return callable
     */
    protected function getConnectionInitializerByString(OptionsInterface $options, string $description)
    {
        switch ($description) {
            case 'redis':
            case 'redis-cluster':
                return function ($parameters, $options, $option) {
                    return new RedisCluster($options->connections, new RedisStrategy($options->crc16));
                };

            case 'predis':
                return $this->getDefaultConnectionInitializer();

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s expects either `predis`, `redis` or `redis-cluster` as valid string values, `%s` given',
                    static::class,
                    $description
                ));
        }
    }

    /**
     * Returns the default connection initializer.
     *
     * @return callable
     */
    protected function getDefaultConnectionInitializer()
    {
        return function ($parameters, $options, $option) {
            return new PredisCluster();
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return $this->getConnectionInitializer(
            $options,
            $this->getDefaultConnectionInitializer()
        );
    }
}
