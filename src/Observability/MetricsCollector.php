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

use OpenTelemetry\API\Metrics\CounterInterface;
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
use Predis\PredisException;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Throwable;

/**
 * Owns the OpenTelemetry instruments of the enabled metric groups and records measurements on them.
 */
class MetricsCollector
{
    public const METER_NAME = 'predis';

    /**
     * Lower-cased messages of the ERR replies reported in the auth category.
     */
    protected const AUTH_ERRORS = [
        'invalid password',
        "wrong number of arguments for 'auth' command",
        'auth <password> called without any password configured for the default user. are you sure your configuration is correct?',
        'client sent auth, but no password is set',
    ];

    /**
     * Lower-cased messages of the ERR replies reported in the network category.
     */
    protected const NETWORK_ERRORS = [
        'max number of clients reached',
        'problem with ldap service',
    ];

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
     * @var CounterInterface|null
     */
    protected $errors;

    /**
     * @var HistogramInterface|null
     */
    protected $connectionCreateTime;

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

        if ($config->isGroupEnabled(MetricGroup::CONNECTION_BASIC)) {
            $this->connectionCreateTime = $meter->createHistogram(
                'db.client.connection.create_time',
                's',
                'Time it took to create a new connection, handshake included.',
                ['ExplicitBucketBoundaries' => $config->getBucketsConnectionCreateTime()]
            );
        }

        if ($config->isGroupEnabled(MetricGroup::RESILIENCY)) {
            $this->errors = $meter->createCounter(
                'redis.client.errors',
                '{error}',
                'Errors returned to the application or handled by the client.'
            );
        }
    }

    /**
     * Tells if the given operation must go through measureOperation(), to record its duration or its errors.
     *
     * @param  string $operation Command ID, PIPELINE or MULTI.
     * @return bool
     */
    public function tracksOperation(string $operation): bool
    {
        return $this->tracksErrors() || $this->tracksDuration($operation);
    }

    /**
     * @param  string $operation
     * @return bool
     */
    protected function tracksDuration(string $operation): bool
    {
        return $this->operationDuration !== null
            && $this->operationDuration->isEnabled()
            && $this->config->shouldTrackCommand($operation);
    }

    /**
     * @return bool
     */
    protected function tracksErrors(): bool
    {
        return $this->errors !== null && $this->errors->isEnabled();
    }

    /**
     * Returns the time to pass to recordConnectionCreateTime() once the connection is established,
     * or NULL when the creation of connections is not measured.
     *
     * @return int|float|null
     */
    public function startConnectionTimer()
    {
        return $this->connectionCreateTime !== null && $this->connectionCreateTime->isEnabled() ? hrtime(true) : null;
    }

    /**
     * Records the time it took to establish a connection.
     *
     * @param NodeConnectionInterface $connection
     * @param int|float               $start      Value returned by startConnectionTimer().
     */
    public function recordConnectionCreateTime(NodeConnectionInterface $connection, $start): void
    {
        $duration = (hrtime(true) - $start) / 1e9;

        try {
            $attributes = $this->nodeAttributes($connection);
            unset($attributes['network.peer.address'], $attributes['network.peer.port']);

            $this->connectionCreateTime->record($duration, $this->buildAttributes($attributes));
        } catch (Throwable $exception) {
            // Telemetry must never break the operation it observes.
        }
    }

    /**
     * Records a failure that is about to be retried and counts the retry of the operation being measured.
     *
     * @param Throwable $error
     */
    public function recordRetry(Throwable $error): void
    {
        $this->recordInternalError($error);
        ++$this->retryAttempts;
    }

    /**
     * Records an error handled by the client instead of being returned to the application,
     * such as a failure that is retried or a cluster redirection.
     *
     * @param Throwable|ErrorResponseInterface $error
     * @param NodeConnectionInterface|null     $node  Node that caused the error, when known.
     */
    public function recordInternalError($error, ?NodeConnectionInterface $node = null): void
    {
        if ($node === null && $error instanceof CommunicationException) {
            $node = $error->getConnection();
        }

        $this->recordError($error, $node, true);
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
            $this->recordOperation($operation, $start, $connection, $command, $batchSize, $exception);

            throw $exception;
        }

        if ($command === null && $batchSize === null && is_array($response)) {
            $batchSize = count($response);
        }

        $error = $response instanceof ErrorResponseInterface ? $response : null;
        $this->recordOperation($operation, $start, $connection, $command, $batchSize, $error);

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
    protected function recordOperation(
        string $operation,
        $start,
        ConnectionInterface $connection,
        ?CommandInterface $command,
        ?int $batchSize,
        $error
    ): void {
        $duration = (hrtime(true) - $start) / 1e9;

        try {
            $node = $this->resolveNode($connection, $command, $error);
        } catch (Throwable $exception) {
            $node = null;
        }

        if ($error !== null) {
            $this->recordError($error, $node, false);
        }

        if (!$this->tracksDuration($operation)) {
            return;
        }

        try {
            $attributes = [
                'db.operation.name' => $operation,
                'redis.client.operation.retry_attempts' => $this->retryAttempts,
            ];

            if ($batchSize !== null) {
                $attributes['db.operation.batch.size'] = $batchSize;
            }

            $attributes += $this->nodeAttributes($node);
            $attributes += $this->errorAttributes($error);

            $this->operationDuration->record($duration, $this->buildAttributes($attributes));
        } catch (Throwable $exception) {
            // Telemetry must never break the operation it observes.
        }
    }

    /**
     * Counts an error on redis.client.errors.
     *
     * @param Throwable|ErrorResponseInterface $error
     * @param NodeConnectionInterface|null     $node
     * @param bool                             $internal TRUE when the client handled the error itself.
     */
    protected function recordError($error, ?NodeConnectionInterface $node, bool $internal): void
    {
        if (!$this->tracksErrors()) {
            return;
        }

        try {
            $attributes = $this->nodeAttributes($node) + $this->errorAttributes($error) + [
                'redis.client.errors.internal' => $internal,
                'redis.client.operation.retry_attempts' => $this->retryAttempts,
            ];
            unset($attributes['db.namespace']);

            $this->errors->add(1, $this->buildAttributes($attributes));
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
        // Categories follow redis-py, which also classifies a few generic ERR replies by their message.
        if ($error instanceof ErrorResponseInterface) {
            $parts = explode(' ', $error->getMessage(), 2);
            $status = $parts[0];
            $message = $status === 'ERR' ? strtolower($parts[1] ?? '') : null;

            if (in_array($status, ['NOAUTH', 'WRONGPASS', 'NOPERM'], true) || in_array($message, self::AUTH_ERRORS, true)) {
                $category = 'auth';
            } elseif ($status === 'LOADING' || in_array($message, self::NETWORK_ERRORS, true)) {
                $category = 'network';
            } else {
                $category = 'server';
            }

            // The same type whether the error is thrown or returned as a response object.
            $attributes = ['error.type' => 'ServerException', 'redis.client.errors.category' => $category];

            // A reply that does not start with an error code, such as "Syntax error at offset 3", has no status code.
            if (preg_match('/^[A-Z][A-Z0-9_]{0,63}$/', $status)) {
                $attributes['db.response.status_code'] = $status;
            }

            return $attributes;
        }

        if ($error instanceof CommunicationException || $error instanceof StreamInitException) {
            $category = 'network';
        } else {
            // Like redis-py, errors raised by the client itself count as server errors.
            $category = $error instanceof PredisException ? 'server' : 'other';
        }

        return [
            // Class name without its namespace.
            'error.type' => substr(strrchr('\\' . get_class($error), '\\'), 1),
            'redis.client.errors.category' => $category,
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
