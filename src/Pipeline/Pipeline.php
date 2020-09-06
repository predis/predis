<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use AppendIterator;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use Predis\ClientContextInterface;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Pipeline\Queue\CommandQueueException;
use Predis\Pipeline\Queue\CommandQueueInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\Iterator\MultiBulkIterator;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use Throwable;
use Traversable;

/**
 * Abstraction for pipelining commands to Redis.
 *
 * Pipelines can use different underlying command queue implementations in order
 * to change the behaviour of how commands are flushed over the connection, for
 * example by using a fire-and-forget approach or by wrapping the whole pipeline
 * in a MULTI / EXEC transaction block.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Pipeline implements ClientContextInterface, Countable, IteratorAggregate
{
    private $executing = false;
    private $pending = false;
    private $traversable = false;
    private $throwOnErrorResponse = false;
    /** @var ClientInterface */
    private $client;
    /** @var CommandQueueInterface */
    private $queue;
    /** @var NoRewindIterator|AppendIterator */
    private $responses;

    /**
     * @param ClientInterface       $client     Client instance used by the pipeline
     * @param CommandQueueInterface $queue      Optional command queue implementation for pipeline execution
     * @param bool                  $travesable Flag to return responses as a traversable iterator or an array
     */
    public function __construct(ClientInterface $client, CommandQueueInterface $queue = null, bool $traversable = true)
    {
        $this->client = $client;
        $this->queue = $queue ?? new Queue\Basic();
        $this->traversable = $traversable;
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        // NOTE: it is safer to force-close the underlying connection on pending
        // traversable responses to avoid protocol desynchronization issues when
        // the pipeline goes out of scope and the GC kicks in, especially after
        // a script terminates and connections are configured to be persistent.
        if ($this->pending && $this->traversable) {
            $this->client->disconnect();
        }
    }

    /**
     * Queues the command instance into the pipeline queue.
     *
     * @param CommandInterface $command Command to be queued into the pipeline
     *
     * @return object
     */
    protected function recordCommand(CommandInterface $command): object
    {
        if ($this->executing) {
            $message = 'The pipeline context is still executing';
            if ($this->traversable) {
                $message .= ' (iteration over responses may not be concluded yet)';
            }

            throw new PipelineException($this, $message);
        }

        $this->queue->enqueue($command);

        return $this;
    }

    /**
     * Stores a command into the pipeline for transmission.
     *
     * @param string $method    Command ID
     * @param array  $arguments Arguments for the command
     *
     * @return $this|mixed
     */
    public function __call(string $method, array $arguments)
    {
        $command = $this->client->createCommand($method, $arguments);
        return $this->recordCommand($command);
    }

    /**
     * Stores a command instance into the pipeline for transmission.
     *
     * @param CommandInterface $command Command to be queued into the pipeline
     *
     * @return $this|mixed
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->recordCommand($command);
    }

    /**
     * Returns the underlying connection to be used by the pipeline.
     *
     * @return ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface
    {
        $connection = $this->getClient()->getConnection();

        if ($connection instanceof ReplicationInterface) {
            $connection->switchToMaster();
        }

        return $connection;
    }

    /**
     * Flushes the pipeline over the target connection and returns responses.
     *
     * @param ConnectionInterface $connection Target connection
     *
     * @return Traversable
     */
    protected function executePipeline(ConnectionInterface $connection): Traversable
    {
        $this->pending = true;

        /** @var Traversable */
        $responses = null;
        /** @var CommandInterface */
        $command = null;

        try {
            $responses = $this->queue->flush($connection);

            foreach ($responses as $command => $response) {
                if ($response instanceof ResponseInterface) {
                    if ($response instanceof ErrorResponseInterface) {
                        $response = $this->onResponseError($connection, $command, $response);
                    } elseif ($response instanceof MultiBulkIterator) {
                        $response = $this->onResponseTraversable($connection, $command, $response);
                    }
                } else {
                    $response = $command->parseResponse($response);
                }

                yield $command => $response;
            }
        } catch (CommandQueueException $exception) {
            $this->onExceptionDuringExecution($exception, $connection, $responses, $command);

            throw new PipelineException($this, $exception->getMessage(), $exception->getCode(), $exception);
        } catch (Exception $exception) {
            $this->onExceptionDuringExecution($exception, $connection, $responses, $command);

            throw $exception;
        } finally {
            $this->executing = false;
        }

        // NOTE: this flag gets set only when the generator properly reaches the
        // end of the iteration, otherwise this is skipped. This could happen if
        // the script terminates before the end of an iteration or an unhandled
        // exception occurs. The flags is used in __destruct() to verify when we
        // should drop the underlying connection in order to avoid any protocol
        // desynchronization issue.
        $this->pending = false;
    }

    /**
     * Performs clean-ups when an exception occurs during pipeline execution.
     *
     * @param Throwable           $exception  Exception thrown during pipeline
     * @param ConnectionInterface $connection Redis connection that returned the error
     * @param ?Traversable        $responses  Current reponse set returned by Redis
     * @param ?CommandInterface   $command    Command affected by the error
     */
    protected function onExceptionDuringExecution(
        Throwable $exception,
        ConnectionInterface $connection,
        ?Traversable $responses = null,
        ?CommandInterface $command = null): void
    {
        $connection->disconnect();
    }

    /**
     * Handles RESP error (prefix `-`) responses returned by Redis.
     *
     * @param ConnectionInterface    $connection Redis connection that returned the error
     * @param CommandInterface       $command    Command affected by the error
     * @param ErrorResponseInterface $response   Error response instance
     *
     * @return mixed
     *
     * @throws ServerException
     */
    protected function onResponseError(ConnectionInterface $connection, CommandInterface $command, ErrorResponseInterface $response)
    {
        if ($this->throwOnErrorResponse) {
            throw new ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Handles traversable RESP array (prefix `*`) responses returned by Redis.
     *
     * @param ConnectionInterface $connection Redis connection that returned the error
     * @param CommandInterface    $command    Command affected by the error
     * @param MultiBulkIterator   $response   Traversable response instance
     *
     * @return iterable
     */
    protected function onResponseTraversable(ConnectionInterface $connection, CommandInterface $command, MultiBulkIterator $response): iterable
    {
        if (!$this->traversable) {
            $response = iterator_to_array($response, false);
        }

        return $response;
    }

    /**
     * Flushes the current pipeline queue.
     *
     * @return $this
     */
    public function flushQueued(): self
    {
        if ($this->traversable) {
            throw new PipelineException($this, sprintf(
                '%s does not support intermediate flushes when configured to return Traversable responses',
                static::class
            ));
        }

        $this->flushQueuedInternal();

        return $this;
    }

    /**
     * Flushes the current pipeline queue (internal method).
     *
     * @return void
     */
    protected function flushQueuedInternal(): void
    {
        $this->executing = true;

        $responses = $this->executePipeline($this->getConnection());

        if ($this->traversable) {
            $this->responses = new Iterator\Responses($responses);

            return;
        }

        if ($this->responses === null) {
            $this->responses = new AppendIterator();
        }

        $this->responses->append(new ArrayIterator(
            iterator_to_array($responses, false)
        ));
    }

    /**
     * Drops the current pipeline queue.
     *
     * @return $this
     */
    public function dropQueued(): self
    {
        $this->queue->reset();

        return $this;
    }

    /**
     * Handles the execution of a pipeline.
     *
     * Execution can be wrapped inside a callable provided by the user and that
     * receives an instance of the pipeline (self) as the only argument. Queued
     * commands will be automatically flushed when the callable returns.
     *
     * @param callable $callable Optional callable for execution
     *
     * @throws Exception
     *
     * @return ?iterable
     */
    public function execute(callable $callable = null): ?iterable
    {
        $responses = null;

        try {
            if ($callable) {
                call_user_func($callable, $this);
            }

            $this->flushQueuedInternal();
        } finally {
            [$responses, $this->responses] = [$this->responses, null];
        }

        if (!$this->traversable) {
            $responses = iterator_to_array($responses, false);
        }

        return $responses;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Traversable
    {
        return is_array($iterable = $this->execute())
            ? new ArrayIterator($iterable)
            : $iterable;
    }

    /**
     * Gets if the pipeline is set to return responses as Travesable instances.
     *
     * @return bool
     */
    public function isTraversable(): bool
    {
        return $this->traversable;
    }

    /**
     * Configures the pipeline to throw an exception on -ERR response.
     *
     * @param bool $value
     */
    public function setThrowOnErrorResponse(bool $value): void
    {
        $this->throwOnErrorResponse = $value;
    }

    /**
     * Returns the current configuration for exceptions on -ERR responses.
     *
     * @return bool
     */
    public function getThrowOnErrorResponse(): bool
    {
        return $this->throwOnErrorResponse;
    }

    /**
     * Returns the underlying client instance used by the pipeline.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
