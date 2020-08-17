<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * Command factory interface.
 *
 * Each Redis command should have a class counterpart and commands factories are
 * used to create new instances of these classes through the library.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface FactoryInterface
{
    /**
     * Checks if the command factory supports the specified command.
     *
     * @param string $commandID Command ID.
     *
     * @return bool
     */
    public function supportsCommand($commandID);

    /**
     * Checks if the command factory supports the specified list of commands.
     *
     * @param array $commandIDs List of command IDs.
     *
     * @return string
     */
    public function supportsCommands(array $commandIDs);

    /**
     * Creates a new command instance.
     *
     * @param string $commandID Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return CommandInterface
     */
    public function createCommand($commandID, array $arguments = array());
}
