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

/**
 * Configures a replica selector used by the redis-cluster connection backend.
 */
interface ReplicasSelectorInterface
{
    /**
     * Adds a link between master and replica connections.
     *
     * @param  string $replicaConnectionId Replica node connection ID
     * @param  string $masterConnectionId  Master node connection ID
     * @return void
     */
    public function addReplica(string $replicaConnectionId, string $masterConnectionId): void;

    /**
     * Returns a random replica's connection ID if the provided operation is read-only.
     *
     * @param  string      $masterConnectionId Master node connection ID
     * @return string|null
     */
    public function getReplicaId(string $masterConnectionId): ?string;
}
