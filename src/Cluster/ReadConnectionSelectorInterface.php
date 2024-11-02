<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use Predis\Command\CommandInterface;

/**
 * Configures a connection selector for read operations used by the redis-cluster connection backend.
 */
interface ReadConnectionSelectorInterface
{
    /**
     * Adds a link between master and replica connections.
     *
     * @param  string $replicaConnectionId Replica node connection ID
     * @param  string $masterConnectionId  Master node connection ID
     * @return void
     */
    public function add(string $replicaConnectionId, string $masterConnectionId): void;

    /**
     * Returns a connection ID for read operation if the provided command is read-only.
     *
     * @param  CommandInterface $command            Redis command
     * @param  string           $masterConnectionId Master node connection ID
     * @return string|null
     */
    public function get(CommandInterface $command, string $masterConnectionId): ?string;
}
