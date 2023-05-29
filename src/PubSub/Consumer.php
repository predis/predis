<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\PubSub;

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\Command;
use Predis\NotSupportedException;

/**
 * PUB/SUB consumer abstraction.
 */
class Consumer extends AbstractConsumer
{
    private $client;
    private $options;

    /**
     * @var SubscriptionContext
     */
    private $subscriptionContext;

    /**
     * @param ClientInterface $client  Client instance used by the consumer.
     * @param array           $options Options for the consumer initialization.
     */
    public function __construct(ClientInterface $client, array $options = null)
    {
        $this->options = $options ?: [];

        if (array_key_exists('context', $this->options)) {
            $this->subscriptionContext = $this->options['context'];
        } else {
            $this->subscriptionContext = new SubscriptionContext();
        }

        $this->checkCapabilities($client);

        $this->client = $client;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('ssubscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    /**
     * Returns the underlying client instance used by the pub/sub iterator.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
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
        $response = $this->client->getConnection()->read();

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
}
