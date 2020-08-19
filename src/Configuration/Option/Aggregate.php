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

use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregateConnectionInterface;

/**
 * Configures an aggregate connection used for clustering
 * multiple Redis nodes using various implementations with
 * different algorithms or strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Aggregate implements OptionInterface
{
    /**
     * Wraps a callable to ensure that the returned value is a valid connection.
     *
     * @param OptionsInterface $options  Client options.
     * @param mixed            $callable Callable initializer.
     *
     * @return \Closure
     */
    protected function getConnectionInitializer(OptionsInterface $options, $callable)
    {
        if (!is_callable($callable)) {
            $class = get_called_class();

            throw new \InvalidArgumentException("$class expects a valid callable");
        }

        $option = $this;

        return function ($parameters = null) use ($callable, $options, $option) {
            $connection = call_user_func($callable, $options, $parameters);

            if (!$connection instanceof AggregateConnectionInterface) {
                $class = get_class($option);

                throw new \InvalidArgumentException("$class expects a valid connection type returned by callable initializer");
            }

            return $connection;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        return $this->getConnectionInitializer($options, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return;
    }
}
