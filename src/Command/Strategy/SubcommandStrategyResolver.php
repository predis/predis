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

namespace Predis\Command\Strategy;

use InvalidArgumentException;

class SubcommandStrategyResolver implements StrategyResolverInterface
{
    private const CONTAINER_COMMANDS_NAMESPACE = 'Predis\Command\Strategy\ContainerCommands';

    /**
     * @var ?string
     */
    private $separator;

    public function __construct(?string $separator = null)
    {
        $this->separator = $separator;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $commandId, string $subcommandId): SubcommandStrategyInterface
    {
        $subcommandStrategyClass = ucwords($subcommandId) . 'Strategy';
        $commandDirectoryName = ucwords($commandId);

        if (!is_null($this->separator)) {
            $subcommandStrategyClass = str_replace($this->separator, '', $subcommandStrategyClass);
            $commandDirectoryName = str_replace($this->separator, '', $commandDirectoryName);
        }

        if (class_exists(
            $containerCommandClass = self::CONTAINER_COMMANDS_NAMESPACE . '\\' . $commandDirectoryName . '\\' . $subcommandStrategyClass
        )) {
            return new $containerCommandClass();
        }

        throw new InvalidArgumentException('Non-existing container command given');
    }
}
