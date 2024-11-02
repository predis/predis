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

use InvalidArgumentException;
use Predis\Cluster\ReadConnectionSelector;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures a read connections selector used by the redis-cluster connection backend.
 */
class ScaleReadOperations implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            return call_user_func($value, $options);
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects either a string or a callable value, %s given',
                static::class,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }

        $value = strtolower($value);
        $supportedValues = [ReadConnectionSelector::MODE_REPLICAS, ReadConnectionSelector::MODE_RANDOM];
        if (!in_array($value, $supportedValues)) {
            throw new InvalidArgumentException(
                sprintf('String value for the scale read operations option must be one of: %s', implode(', ', $supportedValues))
            );
        }

        return new ReadConnectionSelector($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        // NOOP
    }
}
