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

class RedisReplicaSelector implements ReplicasSelectorInterface
{
    /**
     * Map of replica connection IDs by a master connection ID
     * [
     *  'master-1:6381' => ['replica-1-1:6391' => true],
     *  'master-2:6382' => ['replica-2-1:6391' => true, 'replica-2-2:6392' => true],
     *  'master-3:6383' => [],
     * ].
     *
     * @var array
     */
    private $replicas = [];

    /**
     * {@inheritdoc}
     */
    public function addReplica(string $replicaConnectionId, string $masterConnectionId): void
    {
        $this->replicas[$masterConnectionId][$replicaConnectionId] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplicaId(string $masterConnectionId): ?string
    {
        if ($replicasMap = $this->replicas[$masterConnectionId] ?? []) {
            $replicas = array_keys($replicasMap);

            return $replicas[array_rand($replicas)];
        }

        return null;
    }
}
