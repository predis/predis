<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

/**
 * Client-side abstraction of a Publish / Subscribe context.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubContext implements \Iterator
{
    const SUBSCRIBE    = 'subscribe';
    const UNSUBSCRIBE  = 'unsubscribe';
    const PSUBSCRIBE   = 'psubscribe';
    const PUNSUBSCRIBE = 'punsubscribe';
    const MESSAGE      = 'message';
    const PMESSAGE     = 'pmessage';

    const STATUS_VALID       = 0x0001;
    const STATUS_SUBSCRIBED  = 0x0010;
    const STATUS_PSUBSCRIBED = 0x0100;

    private $_client;
    private $_position;
    private $_options;

    /**
     * @param Client Client instance used by the context.
     * @param array Options for the context initialization.
     */
    public function __construct(Client $client, Array $options = null)
    {
        $this->checkCapabilities($client);
        $this->_options = $options ?: array();
        $this->_client = $client;
        $this->_statusFlags = self::STATUS_VALID;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    /**
     * Automatically closes the context when PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->closeContext();
    }

    /**
     * Checks if the passed client instance satisfies the required conditions
     * needed to initialize a Publish / Subscribe context.
     *
     * @param Client Client instance used by the context.
     */
    private function checkCapabilities(Client $client)
    {
        if (Helpers::isCluster($client->getConnection())) {
            throw new ClientException(
                'Cannot initialize a PUB/SUB context over a cluster of connections'
            );
        }

        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');

        if ($client->getProfile()->supportsCommands($commands) === false) {
            throw new ClientException(
                'The current profile does not support PUB/SUB related commands'
            );
        }
    }

    /**
     * This method shares the logic to handle both SUBSCRIBE and PSUBSCRIBE.
     *
     * @param string $subscribeAction Type of subscription.
     */
    private function genericSubscribeInit($subscribeAction)
    {
        if (isset($this->_options[$subscribeAction])) {
            $this->$subscribeAction($this->_options[$subscribeAction]);
        }
    }

    /**
     * Checks if the specified flag is valid in the state of the context.
     *
     * @param int $value Flag.
     * @return Boolean
     */
    private function isFlagSet($value)
    {
        return ($this->_statusFlags & $value) === $value;
    }

    /**
     * Subscribes to the specified channels.
     *
     * @param mixed $arg,... One or more channel names.
     */
    public function subscribe(/* arguments */)
    {
        $this->writeCommand(self::SUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_SUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels.
     *
     * @param mixed $arg,... One or more channel names.
     */
    public function unsubscribe(/* arguments */)
    {
        $this->writeCommand(self::UNSUBSCRIBE, func_get_args());
    }

    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param mixed $arg,... One or more channel name patterns.
     */
    public function psubscribe(/* arguments */)
    {
        $this->writeCommand(self::PSUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels using a pattern.
     *
     * @param mixed $arg,... One or more channel name patterns.
     */
    public function punsubscribe(/* arguments */)
    {
        $this->writeCommand(self::PUNSUBSCRIBE, func_get_args());
    }

    /**
     * Closes the context by unsubscribing from all the subscribed channels.
     */
    public function closeContext()
    {
        if ($this->valid()) {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
        }
    }

    /**
     * Writes a Redis command on the underlying connection.
     *
     * @param string $method ID of the command.
     * @param array $arguments List of arguments.
     */
    private function writeCommand($method, $arguments)
    {
        $arguments = Helpers::filterArrayArguments($arguments);
        $command = $this->_client->createCommand($method, $arguments);
        $this->_client->getConnection()->writeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        // NOOP
    }

    /**
     * Returns the last message payload retrieved from the server and generated
     * by one of the active subscriptions.
     *
     * @return array
     */
    public function current()
    {
        return $this->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if ($this->isFlagSet(self::STATUS_VALID)) {
            $this->_position++;
        }

        return $this->_position;
    }

    /**
     * Checks if the the context is still in a valid state to continue.
     *
     * @return Boolean
     */
    public function valid()
    {
        $isValid = $this->isFlagSet(self::STATUS_VALID);
        $subscriptionFlags = self::STATUS_SUBSCRIBED | self::STATUS_PSUBSCRIBED;
        $hasSubscriptions = ($this->_statusFlags & $subscriptionFlags) > 0;

        return $isValid && $hasSubscriptions;
    }

    /**
     * Resets the state of the context.
     */
    private function invalidate()
    {
        $this->_statusFlags = 0x0000;
    }

    /**
     * Waits for a new message from the server generated by one of the active
     * subscriptions and returns it when available.
     *
     * @return array
     */
    private function getValue()
    {
        $response = $this->_client->getConnection()->read();

        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }

            case self::MESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'channel' => $response[1],
                    'payload' => $response[2],
                );

            case self::PMESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'pattern' => $response[1],
                    'channel' => $response[2],
                    'payload' => $response[3],
                );

            default:
                throw new ClientException(
                    "Received an unknown message type {$response[0]} inside of a pubsub context"
                );
        }
    }
}
