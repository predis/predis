<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Observability;

use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\CommunicationException;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\RelayConnection;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Connection\StreamConnection;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Throwable;

/**
 * Owns the OpenTelemetry instruments of the enabled metric groups and records measurements on them.
 */
class MetricsCollector
{
    public const METER_NAME = 'predis';

    /**
     * @var MeterInterface
     */
    protected $meter;

    /**
     * @var MetricsConfig
     */
    protected $config;

    /**
     * @var HistogramInterface|null
     */
    protected $operationDuration;

    /**
     * @var int
     */
    protected $retryAttempts = 0;

    /**
     * @param MeterInterface $meter
     * @param MetricsConfig  $config
     */
    public function __construct(MeterInterface $meter, MetricsConfig $config)
    {
        $this->meter = $meter;
        $this->config = $config;

        if ($config->isGroupEnabled(MetricGroup::COMMAND)) {
            $this->operationDuration = $meter->createHistogram(
                'db.client.operation.duration',
                's',
                'Duration of operations as seen by the application, retries included.',
                ['ExplicitBucketBoundaries' => $config->getBucketsOperationDuration()]
            );
        }
    }

    /**
     * Tells if the duration of the given operation must be measured.
     *
     * @param  string $operation Command ID, PIPELINE or MULTI.
     * @return bool
     */
    public function tracksOperation(string $operation): bool
    {
        return $this->operationDuration !== null
            && $this->operationDuration->isEnabled()
            && $this->config->shouldTrackCommand($operation);
    }

    /**
     * Counts a retry of the operation being measured.
     */
    public function countRetry(): void
    {
        ++$this->retryAttempts;
    }

    /**
     * Runs an operation and records its duration, including the error it ended with.
     *
     * @param  string                $operation  Command ID, PIPELINE or MULTI.
     * @param  ConnectionInterface   $connection Connection of the client running the operation.
     * @param  callable              $callback   Executes the operation and returns its response.
     * @param  CommandInterface|null $command    Command being executed, NULL for pipelines and transactions.
     * @param  int|null              $batchSize  Number of commands in a pipeline or transaction, defaults
     *                                           to the number of responses.
     * @return mixed
     * @throws Throwable
     */
    public function measureOperation(
        string $operation,
        ConnectionInterface $connection,
        callable $callback,
        ?CommandInterface $command = null,
        ?int $batchSize = null
    ) {
        $this->retryAttempts = 0;
        $start = hrtime(true);

        try {
            $response = $callback();
        } catch (Throwable $exception) {
            $this->recordOperationDuration($operation, $start, $connection, $command, $batchSize, $exception);

            throw $exception;
        }

        if ($command === null && $batchSize === null && is_array($response)) {
            $batchSize = count($response);
        }

        $error = $response instanceof ErrorResponseInterface ? $response : null;
        $this->recordOperationDuration($operation, $start, $connection, $command, $batchSize, $error);

        return $response;
    }

    /**
     * @param string                                $operation
     * @param int|float                             $start      Value of hrtime(true) when the operation started.
     * @param ConnectionInterface                   $connection
     * @param CommandInterface|null                 $command
     * @param int|null                              $batchSize
     * @param Throwable|ErrorResponseInterface|null $error
     */
    protected function recordOperationDuration(
        string $operation,
        $start,
        ConnectionInterface $connection,
        ?CommandInterface $command,
        ?int $batchSize,
        $error
    ): void {
        $duration = (hrtime(true) - $start) / 1e9;

        try {
            $attributes = [
                'db.operation.name' => $operation,
                'redis.client.operation.retry_attempts' => $this->retryAttempts,
            ];

            if ($batchSize !== null) {
                $attributes['db.operation.batch.size'] = $batchSize;
            }

            $attributes += $this->nodeAttributes($this->resolveNode($connection, $command, $error));
            $attributes += $this->errorAttributes($error);

            $this->operationDuration->record($duration, $this->buildAttributes($attributes));
        } catch (Throwable $exception) {
            // Telemetry must never break the operation it observes.
        }
    }

    /**
     * Returns the node an operation was executed on, when it is known without contacting any server.
     *
     * @param  ConnectionInterface                   $connection
     * @param  CommandInterface|null                 $command
     * @param  Throwable|ErrorResponseInterface|null $error
     * @return NodeConnectionInterface|null
     */
    protected function resolveNode(ConnectionInterface $connection, ?CommandInterface $command, $error)
    {
        if ($error instanceof CommunicationException) {
            return $error->getConnection();
        }

        if ($connection instanceof NodeConnectionInterface) {
            return $connection;
        }

        if ($connection instanceof ReplicationInterface) {
            return $connection->getCurrent();
        }

        // After a successful command the slots map already points to the node that served it.
        if ($connection instanceof ClusterInterface && $command !== null && $error === null) {
            return $connection->getConnectionByCommand($command);
        }

        return null;
    }

    /**
     * @param  NodeConnectionInterface|null $node
     * @return array<string, mixed>
     */
    protected function nodeAttributes(?NodeConnectionInterface $node): array
    {
        if ($node === null) {
            return [];
        }

        $parameters = $node->getParameters();
        // The database selected when connecting, a later SELECT is not tracked.
        $attributes = ['db.namespace' => (string) ($parameters->database ?? 0)];

        if ($parameters->scheme === 'unix') {
            $attributes['server.address'] = $parameters->path;
        } else {
            $attributes['server.address'] = $parameters->host;
            $attributes['server.port'] = (int) $parameters->port;
        }

        if ($node instanceof StreamConnection && null !== $peer = $node->getPeerName()) {
            $separator = strrpos($peer, ':');

            if ($separator === false) {
                $attributes['network.peer.address'] = $peer;
            } else {
                $attributes['network.peer.address'] = trim(substr($peer, 0, $separator), '[]');
                $attributes['network.peer.port'] = (int) substr($peer, $separator + 1);
            }
        } elseif ($node instanceof RelayConnection) {
            // Relay does not expose the resolved address of its socket, the configured one is the closest known.
            $attributes['network.peer.address'] = $attributes['server.address'];

            if (isset($attributes['server.port'])) {
                $attributes['network.peer.port'] = $attributes['server.port'];
            }
        }

        return $attributes;
    }

    /**
     * @param  Throwable|ErrorResponseInterface|null $error
     * @return array<string, mixed>
     */
    protected function errorAttributes($error): array
    {
        if ($error === null) {
            return [];
        }

        // Error responses and the exceptions created from them carry the error prefix sent by the server.
        if ($error instanceof ErrorResponseInterface) {
            $status = $error->getErrorType();

            return [
                'error.type' => $status,
                'db.response.status_code' => $status,
                'redis.client.errors.category' => in_array($status, ['NOAUTH', 'WRONGPASS', 'NOPERM'], true)
                    ? 'auth'
                    : 'server',
            ];
        }

        $isNetwork = $error instanceof CommunicationException || $error instanceof StreamInitException;

        return [
            'error.type' => get_class($error),
            'redis.client.errors.category' => $isNetwork ? 'network' : 'other',
        ];
    }

    /**
     * @return MetricsConfig
     */
    public function getConfig(): MetricsConfig
    {
        return $this->config;
    }

    /**
     * Completes metric specific attributes with the ones shared by every metric.
     * Custom attributes are applied last, so they win over built-in ones.
     *
     * @param  array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function buildAttributes(array $attributes = []): array
    {
        return $this->config->getCustomAttributes() + $attributes + [
            'db.system.name' => 'redis',
            'redis.client.library' => 'predis:' . Client::VERSION,
        ];
    }
}
