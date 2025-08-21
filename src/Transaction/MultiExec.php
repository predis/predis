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

namespace Predis\Transaction;

use Exception;
use InvalidArgumentException;
use Predis\ClientContextInterface;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\CommunicationException;
use Predis\NotSupportedException;
use Predis\Protocol\ProtocolException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;
use Predis\Response\Status as StatusResponse;
use Predis\Transaction\Response\BypassTransactionResponse;
use Predis\Transaction\Strategy\ConnectionStrategyResolver;
use Predis\Transaction\Strategy\StrategyInterface;
use Predis\Transaction\Strategy\StrategyResolverInterface;
use Relay\Exception as RelayException;
use Relay\Relay;
use SplQueue;

/**
 * Client-side abstraction of a Redis transaction based on MULTI / EXEC.
 *
 * {@inheritdoc}
 */
class MultiExec implements ClientContextInterface
{
    private $state;

    protected $client;
    protected $commands;
    protected $exceptions = true;
    protected $attempts = 0;
    protected $watchKeys = [];
    protected $modeCAS = false;

    /**
     * @var StrategyInterface
     */
    protected $connectionStrategy;

    /**
     * @param  ClientInterface       $client  Client instance used by the transaction.
     * @param  array|null            $options Initialization options.
     * @throws NotSupportedException
     */
    public function __construct(
        ClientInterface $client,
        ?array $options = null,
        ?StrategyResolverInterface $strategyResolver = null
    ) {
        $this->assertClient($client);

        $this->client = $client;
        $this->state = new MultiExecState();

        if (null === $strategyResolver) {
            $strategyResolver = new ConnectionStrategyResolver();
        }

        $this->connectionStrategy = $strategyResolver->resolve(
            $client->getConnection(),
            $this->state
        );
        $this->configure($client, $options ?: []);
        $this->reset();
    }

    /**
     * Checks if the passed client instance satisfies the required conditions
     * needed to initialize the transaction object.
     *
     * @param ClientInterface $client Client instance used by the transaction object.
     *
     * @throws NotSupportedException
     */
    private function assertClient(ClientInterface $client)
    {
        if (!$client->getCommandFactory()->supports('MULTI', 'EXEC', 'DISCARD')) {
            throw new NotSupportedException(
                'MULTI, EXEC and DISCARD are not supported by the current command factory.'
            );
        }
    }

    /**
     * Configures the transaction using the provided options.
     *
     * @param ClientInterface $client  Underlying client instance.
     * @param array           $options Array of options for the transaction.
     **/
    protected function configure(ClientInterface $client, array $options)
    {
        if (isset($options['exceptions'])) {
            $this->exceptions = (bool) $options['exceptions'];
        } else {
            $this->exceptions = $client->getOptions()->exceptions;
        }

        if (isset($options['cas'])) {
            $this->modeCAS = (bool) $options['cas'];
        }

        if (isset($options['watch']) && $keys = $options['watch']) {
            $this->watchKeys = $keys;
        }

        if (isset($options['retry'])) {
            $this->attempts = (int) $options['retry'];
        }
    }

    /**
     * Resets the state of the transaction.
     */
    protected function reset()
    {
        $this->state->reset();
        $this->commands = new SplQueue();
    }

    /**
     * Initializes the transaction context.
     */
    protected function initialize()
    {
        if ($this->state->isInitialized()) {
            return;
        }

        if ($this->modeCAS) {
            $this->state->flag(MultiExecState::CAS);
        }

        if ($this->watchKeys) {
            $this->watch($this->watchKeys);
        }

        $cas = $this->state->isCAS();
        $discarded = $this->state->isDiscarded();

        if (!$cas || ($cas && $discarded)) {
            $this->connectionStrategy->initializeTransaction();

            if ($discarded) {
                $this->state->unflag(MultiExecState::CAS);
            }
        }

        $this->state->unflag(MultiExecState::DISCARDED);
        $this->state->flag(MultiExecState::INITIALIZED);
    }

    /**
     * Dynamically invokes a Redis command with the specified arguments.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->executeCommand(
            $this->client->createCommand($method, $arguments)
        );
    }

    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return $this|mixed
     * @throws AbortedMultiExecException
     * @throws CommunicationException
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->initialize();

        $response = $this->connectionStrategy->executeCommand($command);

        if ($response instanceof BypassTransactionResponse) {
            return $response->getResponse();
        }

        if ($response instanceof StatusResponse && $response == 'QUEUED') {
            $this->commands->enqueue($command);
        } elseif ($response instanceof Relay) {
            $this->commands->enqueue($command);
        } elseif ($response instanceof ErrorResponseInterface) {
            throw new AbortedMultiExecException($this, $response->getMessage());
        } else {
            $this->onProtocolError('The server did not return a +QUEUED status response.');
        }

        return $this;
    }

    /**
     * Executes WATCH against one or more keys.
     *
     * @param string|array $keys One or more keys.
     *
     * @return mixed
     * @throws NotSupportedException
     * @throws ClientException
     */
    public function watch($keys)
    {
        if (!$this->client->getCommandFactory()->supports('WATCH')) {
            throw new NotSupportedException('WATCH is not supported by the current command factory.');
        }

        if ($this->state->isWatchAllowed()) {
            throw new ClientException('Sending WATCH after MULTI is not allowed.');
        }

        $response = $this->connectionStrategy->watch(is_array($keys) ? $keys : [$keys]);
        $this->state->flag(MultiExecState::WATCH);

        return $response;
    }

    /**
     * Finalizes the transaction by executing MULTI on the server.
     *
     * @return MultiExec
     */
    public function multi()
    {
        if ($this->state->check(MultiExecState::INITIALIZED | MultiExecState::CAS)) {
            $this->state->unflag(MultiExecState::CAS);
            $this->connectionStrategy->multi();
        } else {
            $this->initialize();
        }

        return $this;
    }

    /**
     * Executes UNWATCH.
     *
     * @return MultiExec
     * @throws NotSupportedException
     */
    public function unwatch()
    {
        if (!$this->client->getCommandFactory()->supports('UNWATCH')) {
            throw new NotSupportedException(
                'UNWATCH is not supported by the current command factory.'
            );
        }

        $this->state->unflag(MultiExecState::WATCH);
        $this->__call('UNWATCH', []);

        return $this;
    }

    /**
     * Resets the transaction by UNWATCH-ing the keys that are being WATCHed and
     * DISCARD-ing pending commands that have been already sent to the server.
     *
     * @return MultiExec
     */
    public function discard()
    {
        if ($this->state->isInitialized()) {
            if ($this->state->isCAS()) {
                $this->connectionStrategy->unwatch();
            } else {
                $this->connectionStrategy->discard();
            }

            $this->reset();
            $this->state->flag(MultiExecState::DISCARDED);
        }

        return $this;
    }

    /**
     * Executes the whole transaction.
     *
     * @return mixed
     */
    public function exec()
    {
        return $this->execute();
    }

    /**
     * Checks the state of the transaction before execution.
     *
     * @param mixed $callable Callback for execution.
     *
     * @throws InvalidArgumentException
     * @throws ClientException
     */
    private function checkBeforeExecution($callable)
    {
        if ($this->state->isExecuting()) {
            throw new ClientException(
                'Cannot invoke "execute" or "exec" inside an active transaction context.'
            );
        }

        if ($callable) {
            if (!is_callable($callable)) {
                throw new InvalidArgumentException('The argument must be a callable object.');
            }

            if (!$this->commands->isEmpty()) {
                $this->discard();

                throw new ClientException(
                    'Cannot execute a transaction block after using fluent interface.'
                );
            }
        } elseif ($this->attempts) {
            $this->discard();

            throw new ClientException(
                'Automatic retries are supported only when a callable block is provided.'
            );
        }
    }

    /**
     * Handles the actual execution of the whole transaction.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     * @throws CommunicationException
     * @throws AbortedMultiExecException
     * @throws ServerException
     */
    public function execute($callable = null)
    {
        $this->checkBeforeExecution($callable);

        $execResponse = null;
        $attempts = $this->attempts;

        do {
            if ($callable) {
                $this->executeTransactionBlock($callable);
            }

            if ($this->commands->isEmpty()) {
                if ($this->state->isWatching()) {
                    $this->discard();
                }

                return;
            }

            $execResponse = $this->connectionStrategy->executeTransaction();

            // The additional `false` check is needed for Relay,
            // let's hope it won't break anything
            if ($execResponse === null || $execResponse === false) {
                if ($attempts === 0) {
                    throw new AbortedMultiExecException(
                        $this, 'The current transaction has been aborted by the server.'
                    );
                }

                $this->reset();

                continue;
            }

            break;
        } while ($attempts-- > 0);

        $response = [];
        $commands = $this->commands;
        $size = count($execResponse);
        $protocolVersion = $this->client->getConnection()->getParameters()->protocol;

        if ($size !== count($commands)) {
            $this->onProtocolError('EXEC returned an unexpected number of response items.');
        }

        for ($i = 0; $i < $size; ++$i) {
            $cmdResponse = $execResponse[$i];

            if ($this->exceptions && $cmdResponse instanceof ErrorResponseInterface) {
                throw new ServerException($cmdResponse->getMessage());
            }

            if ($cmdResponse instanceof RelayException) {
                if ($this->exceptions) {
                    throw new ServerException($cmdResponse->getMessage(), $cmdResponse->getCode(), $cmdResponse);
                }

                $commands->dequeue();
                $response[$i] = new Error($cmdResponse->getMessage());
                continue;
            }

            if ($protocolVersion === 2) {
                $response[$i] = $commands->dequeue()->parseResponse($cmdResponse);
            } else {
                $response[$i] = $commands->dequeue()->parseResp3Response($cmdResponse);
            }
        }

        return $response;
    }

    /**
     * Passes the current transaction object to a callable block for execution.
     *
     * @param mixed $callable Callback.
     *
     * @throws CommunicationException
     * @throws ServerException
     */
    protected function executeTransactionBlock($callable)
    {
        $exception = null;
        $this->state->flag(MultiExecState::INSIDEBLOCK);

        try {
            call_user_func($callable, $this);
        } catch (CommunicationException $exception) {
            // NOOP
        } catch (ServerException $exception) {
            // NOOP
        } catch (Exception $exception) {
            $this->discard();
        }

        $this->state->unflag(MultiExecState::INSIDEBLOCK);

        if ($exception) {
            throw $exception;
        }
    }

    /**
     * Helper method for protocol errors encountered inside the transaction.
     *
     * @param string $message Error message.
     */
    private function onProtocolError($message)
    {
        // Since a MULTI/EXEC block cannot be initialized when using aggregate
        // connections we can safely assume that Predis\Client::getConnection()
        // will return a Predis\Connection\NodeConnectionInterface instance.
        CommunicationException::handle(new ProtocolException(
            $this->client->getConnection(), $message
        ));
    }
}
