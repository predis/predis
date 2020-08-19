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

use Predis\Command\FactoryInterface;
use Predis\Command\RedisFactory;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures a connection factory to be used by the client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Commands implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }

        if (is_array($value)) {
            $commands = $this->getDefault($options);

            foreach ($value as $commandID => $classFQN) {
                $commands->defineCommand($commandID, $classFQN);
            }

            return $commands;
        }

        if (!$value instanceof FactoryInterface) {
            $class = get_called_class();

            throw new \InvalidArgumentException("$class expects a valid command factory");
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $commands = new RedisFactory();

        if (isset($options->prefix)) {
            $commands->setProcessor($options->prefix);
        }

        return $commands;
    }
}
