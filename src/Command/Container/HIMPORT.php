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
use Predis\Himport\HimportOptions;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\Response\Status;

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
 *  - transparently recover from "no such fieldset" on SET by re-preparing the
 *    fieldset on the executing connection and retrying exactly once (enabled by
 *    default; disable with the `himport` client option `['auto_prepare' => false]`).
 *
 * The recovery layer only ever acts on fieldsets declared through this container.
 * The raw command form ($client->himport('SET', ...)), pipelines and transactions
 * stay fully explicit: they never auto-recover and propagate server errors as-is.
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

        // Record intent before executing: if a fan-out partially fails, lagging
        // nodes will report "no such fieldset" on their first SET and the
        // recovery path re-prepares them from this registry.
        $this->getRegistry()->set($fieldset, $fields);

        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_PREPARE, $fieldset, $fields]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                return $this->surfaceError($error);
            }

            foreach ($results as $result) {
                $this->pinSessionCommand($result[0], $this->sessionKey($fieldset), $command);
            }

            return Status::get('OK');
        }

        $response = $this->client->executeCommand($command);

        if (!$response instanceof ErrorInterface) {
            $this->pinSessionCommand($this->resolveNode($command), $this->sessionKey($fieldset), $command);
        }

        return $response;
    }

    /**
     * Creates or overwrites a hash at $key using the field list previously
     * prepared under $fieldset on the executing connection.
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

        try {
            $response = $this->client->executeCommand($command);
        } catch (ServerException $exception) {
            if ($this->canRecover($exception->getMessage(), $fieldset)) {
                return $this->recoverAndRetry($command, $fieldset);
            }

            throw $exception;
        }

        if ($response instanceof ErrorInterface && $this->canRecover($response->getMessage(), $fieldset)) {
            return $this->recoverAndRetry($command, $fieldset);
        }

        return $response;
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
        $this->getRegistry()->remove($fieldset);

        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_DISCARD, $fieldset]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                return $this->surfaceError($error);
            }

            $removed = 0;

            foreach ($results as $result) {
                $this->unpinSessionCommand($result[0], $this->sessionKey($fieldset));
                $removed = max($removed, (int) $result[1]);
            }

            return $removed;
        }

        $response = $this->client->executeCommand($command);

        if (!$response instanceof ErrorInterface) {
            $this->unpinSessionCommand($this->resolveNode($command), $this->sessionKey($fieldset));
        }

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
        $this->getRegistry()->clear();

        $command = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_DISCARDALL]);
        $connection = $this->client->getConnection();

        if ($connection instanceof ClusterInterface) {
            $results = $this->fanOut($connection, $command);

            if (null !== $error = $this->firstError($results)) {
                return $this->surfaceError($error);
            }

            $removed = 0;

            foreach ($results as $result) {
                $this->unpinSessionCommandsByPrefix($result[0], self::SESSION_KEY_PREFIX);
                $removed = max($removed, (int) $result[1]);
            }

            return $removed;
        }

        $response = $this->client->executeCommand($command);

        if (!$response instanceof ErrorInterface) {
            $this->unpinSessionCommandsByPrefix($this->resolveNode($command), self::SESSION_KEY_PREFIX);
        }

        return $response;
    }

    /**
     * Re-prepares $fieldset on the connection that executed the failed SET, then
     * retries the SET exactly once. A failure of the re-prepare itself is
     * surfaced as the root cause and the SET is not retried.
     *
     * @param  CommandInterface      $setCommand
     * @param  string                $fieldset
     * @return Status|ErrorInterface
     * @throws ServerException
     */
    private function recoverAndRetry(CommandInterface $setCommand, string $fieldset)
    {
        $fields = $this->getRegistry()->get($fieldset);
        $prepare = $this->client->createCommand('HIMPORT', [self::SUBCOMMAND_PREPARE, $fieldset, $fields]);

        // Resolve after the SET failed so that, following a MOVED/ASK redirection,
        // we re-prepare on the node the retry will actually target.
        $node = $this->resolveNode($setCommand);
        $prepareResponse = $node->executeCommand($prepare);

        if ($prepareResponse instanceof ErrorInterface) {
            return $this->surfaceError($prepareResponse);
        }

        $this->pinSessionCommand($node, $this->sessionKey($fieldset), $prepare);

        return $this->client->executeCommand($setCommand);
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
     * @param  ClusterInterface                                        $cluster
     * @param  CommandInterface                                        $command
     * @return array<int, array{0: NodeConnectionInterface, 1: mixed}>
     */
    private function fanOut(ClusterInterface $cluster, CommandInterface $command): array
    {
        $results = [];

        if ($cluster instanceof IteratorAggregate) {
            foreach ($cluster->getIterator() as $node) {
                $results[] = [$node, $node->executeCommand($command)];
            }
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
