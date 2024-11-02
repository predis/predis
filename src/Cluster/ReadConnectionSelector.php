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
use Predis\Command\ReadonlyOperationDetector;
use Predis\Command\ReadonlyOperationDetectorInterface;

class ReadConnectionSelector implements ReadConnectionSelectorInterface
{
    public const MODE_RANDOM = 'random';
    public const MODE_REPLICAS = 'replicas';

    private $mode;

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
     * @var ReadonlyOperationDetectorInterface
     */
    private $readonlyOperationDetector;

    public function __construct(string $mode = self::MODE_REPLICAS, ?ReadonlyOperationDetectorInterface $detector = null)
    {
        $this->mode = $mode;
        $this->readonlyOperationDetector = $detector ?: new ReadonlyOperationDetector();
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $replicaConnectionId, string $masterConnectionId): void
    {
        $this->replicas[$masterConnectionId][$replicaConnectionId] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(CommandInterface $command, string $masterConnectionId): ?string
    {
        if (!$this->readonlyOperationDetector->detect($command)) {
            return null;
        }

        $readConnectionsMap = $this->replicas[$masterConnectionId] ?? [];
        if (empty($readConnectionsMap)) {
            return null;
        }
        $readConnections = array_keys($readConnectionsMap);

        if ($this->mode === self::MODE_RANDOM) {
            // Add master connection to a connection pool
            $readConnections[] = $masterConnectionId;
        }

        return $this->getRandomConnection($readConnections);
    }

    /**
     * Returns a random connection ID from a provided list.
     *
     * @param  array  $connections Redis connection IDs
     * @return string
     */
    protected function getRandomConnection(array $connections): string
    {
        return $connections[array_rand($connections)];
    }
}
