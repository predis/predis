<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use Predis\Command\FactoryInterface;
use Predis\Command\RedisFactory;

/**
 * Configures a connection factory to be used by the client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CommandsOption implements OptionInterface
{
    /**
     * Sets the commands processors to be used by the command factory.
     *
     * @param OptionsInterface $options Client options.
     * @param FactoryInterface $factory Command factory.
     */
    protected function setProcessors(OptionsInterface $options, FactoryInterface $factory)
    {
        if (isset($options->prefix)) {
            // NOTE: directly using __get('prefix') is actually a workaround for
            // HHVM 2.3.0. It's correct and respects the options interface, it's
            // just ugly. We will remove this hack when HHVM will fix re-entrant
            // calls to __get() once and for all.

            $factory->setProcessor($options->__get('prefix'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (!$value instanceof FactoryInterface) {
            throw new \InvalidArgumentException('Invalid value for the commands option.');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $commands = new RedisFactory();
        $this->setProcessors($options, $commands);

        return $commands;
    }
}
