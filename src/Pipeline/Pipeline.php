<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Exception;
use InvalidArgumentException;
use Predis\ClientContextInterface;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use SplQueue;

/**
 * Implementation of a command pipeline in which write and read operations of
 * Redis commands are pipelined to alleviate the effects of network round-trips.
 *
 * {@inheritdoc}
 */
class Pipeline implements ClientContextInterface
{
    protected $client;
    private $pipeline;

    private $responses = [];
    private $running = false;

    /**
     * @param ClientInterface $client Client instance used by the context.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->pipeline = new SplQueue();
    }

    /**
     * Queues a command into the pipeline buffer.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return $this
     */
    public function __call($method, $arguments)
    {
        $command = $this->client->createCommand($method, $arguments);
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command to be queued in the buffer.
     */
    protected function recordCommand(CommandInterface $command)
    {
        $this->pipeline->enqueue($command);
    }

    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command instance to be queued in the buffer.
     *
     * @return $this
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Throws an exception on -ERR responses returned by Redis.
     *
     * @param ConnectionInterface    $connection Redis connection that returned the error.
     * @param ErrorResponseInterface $response   Instance of the error response.
     *
     * @throws ServerException
     */
    protected function exception(ConnectionInterface $connection, ErrorResponseInterface $response)
    {
        $connection->disconnect();
        $message = $response->getMessage();

        throw new ServerException($message);
    }

    /**
     * Returns the underlying connection to be used by the pipeline.
     *
     * @return ConnectionInterface
     */
    protected function getConnection()
    {
        $connection = $this->getClient()->getConnection();

        if ($connection instanceof ReplicationInterface) {
            $connection->switchToMaster();
        }

        return $connection;
    }

    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param ConnectionInterface $connection Current connection instance.
     * @param SplQueue            $commands   Queued commands.
     *
     * @return array
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        $buffer = '';

        foreach ($commands as $command) {
            $buffer .= $command->serializeCommand();
        }

        $connection->write($buffer);

        $responses = [];
        $exceptions = $this->throwServerExceptions();
        $protocolVersion = (int) $connection->getParameters()->protocol;

        while (!$commands->isEmpty()) {
            $command = $commands->dequeue();
            $response = $connection->readResponse($command);

            if (!$response instanceof ResponseInterface) {
                if ($protocolVersion === 2) {
                    $responses[] = $command->parseResponse($response);
                } else {
                    $responses[] = $command->parseResp3Response($response);
                }
            } elseif ($response instanceof ErrorResponseInterface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }
        }

        return $responses;
    }

    /**
     * Flushes the buffer holding all of the commands queued so far.
     *
     * @param bool $send Specifies if the commands in the buffer should be sent to Redis.
     *
     * @return $this
     */
    public function flushPipeline($send = true)
    {
        if ($send && !$this->pipeline->isEmpty()) {
            $responses = $this->executePipeline($this->getConnection(), $this->pipeline);
            $this->responses = array_merge($this->responses, $responses);
        } else {
            $this->pipeline = new SplQueue();
        }

        return $this;
    }

    /**
     * Marks the running status of the pipeline.
     *
     * @param bool $bool Sets the running status of the pipeline.
     *
     * @throws ClientException
     */
    private function setRunning($bool)
    {
        if ($bool && $this->running) {
            throw new ClientException('The current pipeline context is already being executed.');
        }

        $this->running = $bool;
    }

    /**
     * Handles the actual execution of the whole pipeline.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute($callable = null)
    {
        if ($callable && !is_callable($callable)) {
            throw new InvalidArgumentException('The argument must be a callable object.');
        }

        $exception = null;
        $this->setRunning(true);

        try {
            if ($callable) {
                call_user_func($callable, $this);
            }

            $this->flushPipeline();
        } catch (Exception $exception) {
            // NOOP
        }

        $this->setRunning(false);

        if ($exception) {
            throw $exception;
        }

        return $this->responses;
    }

    /**
     * Returns if the pipeline should throw exceptions on server errors.
     *
     * @return bool
     */
    protected function throwServerExceptions()
    {
        return (bool) $this->client->getOptions()->exceptions;
    }

    /**
     * Returns the underlying client instance used by the pipeline object.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
