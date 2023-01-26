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

/**
 * Command factory interface.
 *
 * A command factory is used through the library to create instances of commands
 * classes implementing Predis\Command\CommandInterface mapped to Redis commands
 * by their command ID string (SET, GET, etc...).
 */
interface FactoryInterface
{
    /**
     * Checks if the command factory supports the specified list of commands.
     *
     * @param string ...$commandIDs List of command IDs
     *
     * @return bool
     */
    public function supports(string ...$commandIDs): bool;

    /**
     * Creates a new command instance.
     *
     * @param string $commandID Command ID
     * @param array  $arguments Arguments for the command
     *
     * @return CommandInterface
     */
    public function create(string $commandID, array $arguments = []): CommandInterface;
}
