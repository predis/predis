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

namespace Predis\Command\Strategy;

use InvalidArgumentException;

class SubcommandStrategyResolver implements StrategyResolverInterface
{
    private const CONTAINER_COMMANDS_NAMESPACE = 'Predis\Command\Strategy\ContainerCommands';

    /**
     * {@inheritDoc}
     */
    public function resolve(string $commandId, string $subcommandId): SubcommandStrategyInterface
    {
        $subcommandStrategyClass = ucfirst(strtolower($subcommandId)) . 'Strategy';
        $commandDirectoryName = ucfirst(strtolower($commandId));

        if (class_exists(
            $containerCommandClass = self::CONTAINER_COMMANDS_NAMESPACE . '\\' . $commandDirectoryName . '\\' . $subcommandStrategyClass
        )) {
            return new $containerCommandClass();
        }

        throw new InvalidArgumentException('Non-existing container command given');
    }
}
