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

use Predis\IConnectionFactory;
use Predis\ConnectionFactory;

/**
 * Option class that returns a connection factory to be used by a client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientConnectionFactory extends Option
{
    /**
     * {@inheritdoc}
     */
    public function filter(IClientOptions $options, $value)
    {
        if ($value instanceof IConnectionFactory) {
            return $value;
        }
        if (is_array($value)) {
            $factory = $this->getDefault($options);
            foreach ($value as $scheme => $initializer) {
                $factory->define($scheme, $initializer);
            }
            return $factory;
        }
        if (is_string($value) && class_exists($value)) {
            if (!($factory = new $value()) && !$factory instanceof IConnectionFactory) {
                throw new \InvalidArgumentException("Class $value must be an instance of Predis\IConnectionFactory");
            }
            return $factory;
        }

        throw new \InvalidArgumentException('Invalid value for the connections option');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(IClientOptions $options)
    {
        return new ConnectionFactory();
    }
}
