<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Cluster\RedisReplicaSelector;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures a replica selector used by the redis-cluster connection backend.
 */
class ReadonlyReplicas implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            return call_user_func($value, $options);
        }

        if ($value === null || $value === false || $value === '' || $value === 'off' | $value === 'false') {
            return null;
        }

        return new RedisReplicaSelector();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        // NOOP
    }
}
