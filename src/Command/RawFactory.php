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
 * Command factory creating raw command instances out of command IDs.
 *
 * Any command ID will produce a command instance even for unknown commands that
 * are not implemented by Redis (the server will return a "-ERR unknown command"
 * error responses).
 *
 * When using this factory the client does not process arguments before sending
 * commands to Redis and server responses are not further processed before being
 * returned to the caller.
 */
class RawFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string ...$commandIDs): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $commandID, array $arguments = []): CommandInterface
    {
        return new RawCommand($commandID, $arguments);
    }
}
