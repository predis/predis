<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\Resolver\CommandResolverInterface;

/**
 * Command factory for mainline Redis servers.
 *
 * This factory is intended to handle standard commands implemented by mainline
 * Redis servers. By default it maps a command ID to a specific command handler
 * class in the Predis\Command\Redis namespace but this can be overridden for
 * any command ID simply by defining a new command handler class implementing
 * Predis\Command\CommandInterface.
 */
class RedisFactory extends Factory
{
    /**
     * @var CommandResolverInterface
     */
    private $commandResolver;

    public function __construct(CommandResolverInterface $commandResolver)
    {
        $this->commands = [
            'ECHO' => 'Predis\Command\Redis\ECHO_',
            'EVAL' => 'Predis\Command\Redis\EVAL_',
            'OBJECT' => 'Predis\Command\Redis\OBJECT_',
        ];

        $this->commandResolver = $commandResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandClass(string $commandID): ?string
    {
        $commandID = strtoupper($commandID);

        if (isset($this->commands[$commandID]) || array_key_exists($commandID, $this->commands)) {
            return $this->commands[$commandID];
        }

        $commandClass = $this->commandResolver->resolve($commandID);

        if (null === $commandClass) {
            return null;
        }

        $this->commands[$commandID] = $commandClass;

        return $commandClass;
    }

    /**
     * {@inheritdoc}
     */
    public function undefine(string $commandID): void
    {
        // NOTE: we explicitly associate `NULL` to the command ID in the map
        // instead of the parent's `unset()` because our subclass tries to load
        // a predefined class from the Predis\Command\Redis namespace when no
        // explicit mapping is defined, see RedisFactory::getCommandClass() for
        // details of the implementation of this mechanism.
        $this->commands[strtoupper($commandID)] = null;
    }
}
