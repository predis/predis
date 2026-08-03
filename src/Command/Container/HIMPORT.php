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

namespace Predis\Command\Container;

use InvalidArgumentException;
use IteratorAggregate;
use Predis\Command\CommandInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Himport\FieldsetNotPreparedException;
use Predis\Himport\HimportOptions;
use Predis\NotSupportedException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Retry\Retry;
use Throwable;

/**
 * Container for the HIMPORT command family (Hinted Hash Templates).
 *
 * HIMPORT fieldsets are server-side session state scoped to a single physical
 * connection: they are lost on disconnect, RESET and failover. This container
 * is a convenience layer over the raw command that keeps a client-side registry
 * of prepared fieldsets so it can:
 *
 *  - fan PREPARE/DISCARD/DISCARDALL out to every master shard on a cluster
 *    (HIMPORT SET is routed normally by the hash slot of its key);
 *  - recover from "no such fieldset" on SET by re-preparing the fieldset on the
 *    executing connection and retrying. This reuses the client-configured Retry
 *    policy, so it happens only when retries are enabled and never more than the
 *    configured number of attempts; with retries disabled the error propagates.
 *    Recovery can additionally be turned off with the `himport` client option
 *    `['auto_prepare' => false]`.
 *
 * The recovery layer only ever acts on fieldsets declared through this container
 * (or the `himport` option). The raw command form ($client->himport('SET', ...)),
 * pipelines and transactions stay fully explicit: they never recover and
 * propagate server errors as-is.
 *
 * @experimental This API is experimental and may change in a future release.
 *
 * @method Status prepare(string $fieldset, array $fields)
 * @method Status set(string $key, string $fieldset, array $values)
 * @method int    discard(string $fieldset)
 * @method int    discardAll()
 */
class HIMPORT extends AbstractContainer
{
    private const SUBCOMMAND_PREPARE = 'PREPARE';
    private const SUBCOMMAND_SET = 'SET';
    private const SUBCOMMAND_DISCARD = 'DISCARD';
    private const SUBCOMMAND_DISCARDALL = 'DISCARDALL';

    private const NO_SUCH_FIELDSET = 'no such fieldset';
    private const SESSION_KEY_PREFIX = 'himport:';

    public function getContainerCommandId(): string
    {
        return 'HIMPORT';
    }

    /**
     * Registers an ordered field list under a fieldset name for use by later
     * SET calls. On a cluster this is fanned out to every master shard.
     *
     * @param  string                $fieldset
     * @param  array                 $fields   Ordered, non-empty field names (sent verbatim).
     * @return Status|ErrorInterface
     * @throws ServerException
     */
    public function prepare(string $fieldset, array $fields)
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('HIMPORT PREPARE requires a non-empty list of fields.');
        }

        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_PREPARE, $fieldset, $fields]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                // Do not record a fieldset the server rejected: a poison entry
                // would make every later SET retry a PREPARE that cannot succeed.
                return $this->surfaceError($error);
            }

            // Record and pin only after every master confirmed the PREPARE.
            $this->getRegistry()->set($fieldset, $fields);

            foreach ($results as $result) {
                $this->pinSessionCommand($result[0], $this->sessionKey($fieldset), $command);
            }

            return Status::get('OK');
        }

        $response = $this->client->executeCommand($command);

        if ($response instanceof ErrorInterface) {
            return $response;
        }

        $this->getRegistry()->set($fieldset, $fields);
        $this->pinSessionCommand($this->resolveNode($command), $this->sessionKey($fieldset), $command);

        return $response;
    }

    /**
     * Creates or overwrites a hash at $key using the field list previously
     * prepared under $fieldset on the executing connection.
     *
     * If the fieldset is missing on the executing connection but known to the
     * client, the write is re-prepared and retried through the connection's Retry
     * policy: this happens only when retries are configured, and never more than
     * the configured number of attempts. With retries disabled the "no such
     * fieldset" error propagates unchanged.
     *
     * @param  string                $key
     * @param  string                $fieldset
     * @param  array                 $values   Ordered, non-empty values paired positionally with the prepared fields.
     * @return Status|ErrorInterface
     * @throws ServerException
     */
    public function set(string $key, string $fieldset, array $values)
    {
        if (empty($values)) {
            throw new InvalidArgumentException('HIMPORT SET requires a non-empty list of values.');
        }

        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_SET, $key, $fieldset, $values]);
        $retry = $this->getConfiguredRetry();

        try {
            if (null === $retry) {
                // No retry policy available: run once, no recovery.
                return $this->executeSet($command, $fieldset);
            }

            // Reuse the client-configured Retry (its enabled/disabled state, count
            // and backoff). Register our retryable condition on it, the same way
            // the cluster and sentinel connections register theirs.
            $retry->updateCatchableExceptions([FieldsetNotPreparedException::class]);

            return $retry->callWithRetry(
                function () use ($command, $fieldset) {
                    return $this->executeSet($command, $fieldset);
                },
                function (Throwable $exception) use ($command, $fieldset) {
                    // Only HIMPORT's own "no such fieldset" is recovered and
                    // retried here; anything else the shared policy might catch
                    // (connection errors, other server errors) is left to its
                    // owner and propagated immediately.
                    if (!$exception instanceof FieldsetNotPreparedException) {
                        throw $exception;
                    }

                    $this->reprepare($command, $fieldset);
                }
            );
        } catch (FieldsetNotPreparedException $exception) {
            // Retries were disabled or exhausted. Preserve the client's error
            // semantics: return the error response when exceptions are off.
            if (!$this->client->getOptions()->exceptions) {
                return new Error($exception->getMessage());
            }

            throw $exception;
        }
    }

    /**
     * Removes a fieldset from the connection session. On a cluster this is
     * fanned out to every master shard.
     *
     * @param  string             $fieldset
     * @return int|ErrorInterface Number of removals (1 if present, 0 otherwise).
     * @throws ServerException
     */
    public function discard(string $fieldset)
    {
        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_DISCARD, $fieldset]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                // Leave the registry and pins untouched so the fieldset stays
                // consistently known and the discard can be retried.
                return $this->surfaceError($error);
            }

            // Drop registry entry and pins together, only after every master
            // confirmed the discard, so the two never disagree.
            $this->getRegistry()->remove($fieldset);
            $removed = 0;

            foreach ($results as $result) {
                $this->unpinSessionCommand($result[0], $this->sessionKey($fieldset));
                $removed = max($removed, (int) $result[1]);
            }

            return $removed;
        }

        $response = $this->client->executeCommand($command);

        if ($response instanceof ErrorInterface) {
            return $response;
        }

        $this->getRegistry()->remove($fieldset);
        $this->unpinSessionCommand($this->resolveNode($command), $this->sessionKey($fieldset));

        return $response;
    }

    /**
     * Removes all fieldsets from the connection session. On a cluster this is
     * fanned out to every master shard.
     *
     * @return int|ErrorInterface Number of fieldsets removed.
     * @throws ServerException
     */
    public function discardAll()
    {
        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_DISCARDALL]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                // Leave client-side state intact so it stays consistent and the
                // discard can be retried.
                return $this->surfaceError($error);
            }

            $this->getRegistry()->clear();
            $removed = 0;

            foreach ($results as $result) {
                $this->unpinSessionCommandsByPrefix($result[0], self::SESSION_KEY_PREFIX);
                $removed = max($removed, (int) $result[1]);
            }

            return $removed;
        }

        $response = $this->client->executeCommand($command);

        if ($response instanceof ErrorInterface) {
            return $response;
        }

        $this->getRegistry()->clear();
        $this->unpinSessionCommandsByPrefix($this->resolveNode($command), self::SESSION_KEY_PREFIX);

        return $response;
    }

    /**
     * Executes the SET, translating a recoverable "no such fieldset" into a
     * retryable exception so the Retry policy can drive re-prepare + retry.
     * Non-recoverable errors are returned/propagated unchanged.
     *
     * @param  CommandInterface             $command
     * @param  string                       $fieldset
     * @return Status|ErrorInterface
     * @throws FieldsetNotPreparedException
     * @throws ServerException
     */
    private function executeSet(CommandInterface $command, string $fieldset)
    {
        try {
            $response = $this->client->executeCommand($command);
        } catch (ServerException $exception) {
            if ($this->canRecover($exception->getMessage(), $fieldset)) {
                throw new FieldsetNotPreparedException($exception->getMessage());
            }

            throw $exception;
        }

        if ($response instanceof ErrorInterface && $this->canRecover($response->getMessage(), $fieldset)) {
            throw new FieldsetNotPreparedException($response->getMessage());
        }

        return $response;
    }

    /**
     * Re-prepares $fieldset on the connection the (next) SET attempt targets.
     * A failed re-prepare aborts recovery and is surfaced as the root cause,
     * through the same "no such fieldset" path so it honours the client's
     * exceptions option (thrown when on, returned as an Error when off).
     *
     * @param  CommandInterface             $setCommand
     * @param  string                       $fieldset
     * @throws FieldsetNotPreparedException
     */
    private function reprepare(CommandInterface $setCommand, string $fieldset): void
    {
        $fields = $this->getRegistry()->get($fieldset);
        $prepare = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_PREPARE, $fieldset, $fields]);

        // Resolve after the SET failed so that, following a MOVED/ASK redirection,
        // we re-prepare on the node the retry will actually target.
        $node = $this->resolveNode($setCommand);
        $response = $node->executeCommand($prepare);

        if ($response instanceof ErrorInterface) {
            // Surface the PREPARE error as the root cause via set()'s catch, which
            // applies the exceptions option (throw vs. return an Error response).
            throw new FieldsetNotPreparedException($response->getMessage());
        }

        $this->pinSessionCommand($node, $this->sessionKey($fieldset), $prepare);
    }

    /**
     * Returns the Retry policy configured on the executing connection, or null
     * when none is available. HIMPORT does not define its own retry behaviour:
     * it reuses whatever the client is configured with, so retries happen only
     * when (and as many times as) the user enabled them.
     *
     * @return Retry|null
     */
    private function getConfiguredRetry(): ?Retry
    {
        $parameters = $this->client->getConnection()->getParameters();

        if (null !== $parameters && $parameters->retry instanceof Retry) {
            return $parameters->retry;
        }

        return null;
    }

    /**
     * @param string $message  Server error message.
     * @param string $fieldset Fieldset referenced by the failed SET.
     */
    private function canRecover(string $message, string $fieldset): bool
    {
        return false !== stripos($message, self::NO_SUCH_FIELDSET)
            && $this->getOptions()->isAutoPrepareEnabled()
            && $this->getRegistry()->has($fieldset);
    }

    /**
     * Executes a command on every master shard of a cluster connection.
     *
     * Fails fast rather than silently no-op'ing: a cluster that cannot be
     * enumerated, or that yields no master shards, must never look like a
     * successful fan-out to the callers that mutate the registry and pins.
     *
     * @param  ClusterInterface                                        $cluster
     * @param  CommandInterface                                        $command
     * @return array<int, array{0: NodeConnectionInterface, 1: mixed}>
     * @throws NotSupportedException
     */
    private function fanOut(ClusterInterface $cluster, CommandInterface $command): array
    {
        if (!$cluster instanceof IteratorAggregate) {
            throw new NotSupportedException(sprintf(
                "HIMPORT requires an iterable cluster connection to fan out to master shards; '%s' is not iterable.",
                get_class($cluster)
            ));
        }

        $results = [];

        foreach ($cluster->getIterator() as $node) {
            $results[] = [$node, $node->executeCommand($command)];
        }

        if (empty($results)) {
            throw new NotSupportedException(
                'HIMPORT could not fan out: the cluster connection reported no master shards.'
            );
        }

        return $results;
    }

    /**
     * @param  array<int, array{0: NodeConnectionInterface, 1: mixed}> $results
     * @return ErrorInterface|null
     */
    private function firstError(array $results): ?ErrorInterface
    {
        foreach ($results as $result) {
            if ($result[1] instanceof ErrorInterface) {
                return $result[1];
            }
        }

        return null;
    }

    /**
     * @param  ErrorInterface  $error
     * @return ErrorInterface
     * @throws ServerException
     */
    private function surfaceError(ErrorInterface $error)
    {
        if ($this->client->getOptions()->exceptions) {
            throw new ServerException($error->getMessage());
        }

        return $error;
    }

    /**
     * Resolves the physical connection that executes (or executed) $command.
     *
     * @param  CommandInterface        $command
     * @return NodeConnectionInterface
     */
    private function resolveNode(CommandInterface $command): NodeConnectionInterface
    {
        $connection = $this->client->getConnection();

        if ($connection instanceof AggregateConnectionInterface) {
            return $connection->getConnectionByCommand($command);
        }

        return $connection;
    }

    private function pinSessionCommand(NodeConnectionInterface $node, string $key, CommandInterface $command): void
    {
        if (method_exists($node, 'addSessionCommand')) {
            $node->addSessionCommand($key, $command);
        }
    }

    private function unpinSessionCommand(NodeConnectionInterface $node, string $key): void
    {
        if (method_exists($node, 'removeSessionCommand')) {
            $node->removeSessionCommand($key);
        }
    }

    private function unpinSessionCommandsByPrefix(NodeConnectionInterface $node, string $prefix): void
    {
        if (method_exists($node, 'removeSessionCommandsByPrefix')) {
            $node->removeSessionCommandsByPrefix($prefix);
        }
    }

    private function sessionKey(string $fieldset): string
    {
        return self::SESSION_KEY_PREFIX . $fieldset;
    }

    private function getOptions(): HimportOptions
    {
        return $this->client->getOptions()->himport;
    }

    private function getRegistry()
    {
        return $this->getOptions()->getRegistry();
    }
}
