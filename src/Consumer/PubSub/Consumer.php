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

namespace Predis\Consumer\PubSub;

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\Command;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Consumer\AbstractConsumer;
use Predis\NotSupportedException;

/**
 * PUB/SUB consumer abstraction.
 */
class Consumer extends AbstractConsumer
{
    public const SUBSCRIBE = 'subscribe';
    public const SSUBSCRIBE = 'ssubscribe';
    public const UNSUBSCRIBE = 'unsubscribe';
    public const SUNSUBSCRIBE = 'sunsubscribe';
    public const PSUBSCRIBE = 'psubscribe';
    public const PUNSUBSCRIBE = 'punsubscribe';
    public const MESSAGE = 'message';
    public const PMESSAGE = 'pmessage';
    public const PONG = 'pong';

    public const STATUS_VALID = 1;       // 0b0001
    public const STATUS_SUBSCRIBED = 2;  // 0b0010
    public const STATUS_PSUBSCRIBED = 4; // 0b0100
    public const STATUS_SSUBSCRIBED = 8; // 0b1000

    protected $statusFlags = self::STATUS_VALID;

    protected $options;

    /**
     * @var SubscriptionContext
     */
    private $subscriptionContext;

    /**
     * @param  ClientInterface       $client  Client instance used by the consumer.
     * @param  array|null            $options Options for the consumer initialization.
     * @throws NotSupportedException
     */
    public function __construct(ClientInterface $client, ?array $options = null)
    {
        $this->options = $options ?: [];
        $this->setSubscriptionContext($client->getConnection());

        parent::__construct($client);
        $this->checkCapabilities($client);

        $this->client = $client;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('ssubscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    /**
     * Returns subscription context for current instance.
     *
     * @return SubscriptionContext
     */
    public function getSubscriptionContext(): SubscriptionContext
    {
        return $this->subscriptionContext;
    }

    /**
     * Checks if the client instance satisfies the required conditions needed to
     * initialize a PUB/SUB consumer.
     *
     * @param ClientInterface $client Client instance used by the consumer.
     *
     * @throws NotSupportedException
     */
    private function checkCapabilities(ClientInterface $client)
    {
        $commands = ['publish', 'spublish', 'subscribe', 'ssubscribe', 'unsubscribe', 'sunsubscribe', 'psubscribe', 'punsubscribe'];

        if (!$client->getCommandFactory()->supports(...$commands)) {
            throw new NotSupportedException(
                'PUB/SUB commands are not supported by the current command factory.'
            );
        }
    }

    /**
     * This method shares the logic to handle SUBSCRIBE, SSUBSCRIBE, PSUBSCRIBE.
     *
     * @param string $subscribeAction Type of subscription.
     */
    private function genericSubscribeInit($subscribeAction)
    {
        if (isset($this->options[$subscribeAction])) {
            $this->$subscribeAction($this->options[$subscribeAction]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function writeRequest($method, $arguments)
    {
        $this->client->getConnection()->writeRequest(
            $this->client->createCommand($method,
                Command::normalizeArguments($arguments)
            )
        );
    }

    /**
     * Automatically stops the consumer when the garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->stop(true);
    }

    /**
     * Checks if the specified flag is valid based on the state of the consumer.
     *
     * @param int $value Flag.
     *
     * @return bool
     */
    protected function isFlagSet($value)
    {
        return ($this->statusFlags & $value) === $value;
    }

    /**
     * Subscribes to the specified channels.
     *
     * @param string ...$channels One or more channel names.
     */
    public function subscribe(string ...$channels)
    {
        $this->writeRequest(self::SUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_SUBSCRIBED;
    }

    /**
     * Subscribes to the specified shard channels.
     *
     * @param string ...$channels
     */
    public function ssubscribe(string ...$channels)
    {
        $this->writeRequest(self::SSUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_SSUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels.
     *
     * @param string ...$channel One or more channel names.
     */
    public function unsubscribe(...$channel)
    {
        $this->writeRequest(self::UNSUBSCRIBE, func_get_args());
    }

    /**
     * Unsubscribes from the specified shard channels.
     *
     * @param string ...$channels
     */
    public function sunsubscribe(string ...$channels)
    {
        $this->writeRequest(self::SUNSUBSCRIBE, func_get_args());
    }

    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function psubscribe(...$pattern)
    {
        $this->writeRequest(self::PSUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function punsubscribe(...$pattern)
    {
        $this->writeRequest(self::PUNSUBSCRIBE, func_get_args());
    }

    /**
     * PING the server with an optional payload that will be echoed as a
     * PONG message in the pub/sub loop.
     *
     * @param string $payload Optional PING payload.
     */
    public function ping($payload = null)
    {
        $this->writeRequest('PING', [$payload]);
    }

    /**
     * Closes the context by unsubscribing from all the subscribed channels. The
     * context can be forcefully closed by dropping the underlying connection.
     *
     * @param bool $drop Indicates if the context should be closed by dropping the connection.
     *
     * @return bool Returns false when there are no pending messages.
     */
    public function stop(bool $drop = false): bool
    {
        if (!$this->valid()) {
            return false;
        }

        if ($drop) {
            $this->invalidate();
            $this->disconnect();
        } else {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_SSUBSCRIBED)) {
                $this->sunsubscribe();
            }
        }

        return !$drop;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->getValue();
    }

    /**
     * Checks if the consumer is still in a valid state to continue.
     *
     * @return bool
     */
    public function valid()
    {
        $isValid = $this->isFlagSet(self::STATUS_VALID);
        $subscriptionFlags = self::STATUS_SUBSCRIBED | self::STATUS_PSUBSCRIBED | self::STATUS_SSUBSCRIBED;
        $hasSubscriptions = ($this->statusFlags & $subscriptionFlags) > 0;

        return $isValid && $hasSubscriptions;
    }

    /**
     * Resets the state of the consumer.
     */
    protected function invalidate()
    {
        $this->statusFlags = 0;    // 0b0000;
    }

    /**
     * {@inheritdoc}
     */
    protected function disconnect()
    {
        $this->client->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue()
    {
        /** @var NodeConnectionInterface $connection */
        $connection = $this->client->getConnection();
        $response = $connection->read();

        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::SSUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::SUNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }
                // The missing break here is intentional as we must process
                // subscriptions and unsubscriptions as standard messages.
                // no break

            case self::MESSAGE:
                return (object) [
                    'kind' => $response[0],
                    'channel' => $response[1],
                    'payload' => $response[2],
                ];

            case self::PMESSAGE:
                return (object) [
                    'kind' => $response[0],
                    'pattern' => $response[1],
                    'channel' => $response[2],
                    'payload' => $response[3],
                ];

            case self::PONG:
                return (object) [
                    'kind' => $response[0],
                    'payload' => $response[1],
                ];

            default:
                throw new ClientException(
                    "Unknown message type '{$response[0]}' received in the PUB/SUB context."
                );
        }
    }

    /**
     * Set subscription context depends on connection.
     *
     * @param  NodeConnectionInterface $connection
     * @return void
     */
    private function setSubscriptionContext(ConnectionInterface $connection): void
    {
        if ($connection instanceof ClusterInterface) {
            $this->subscriptionContext = new SubscriptionContext(SubscriptionContext::CONTEXT_SHARDED);
        } else {
            $this->subscriptionContext = new SubscriptionContext();
        }
    }
}
