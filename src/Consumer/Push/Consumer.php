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

namespace Predis\Consumer\Push;

use Iterator;
use Predis\ClientInterface;
use ReturnTypeWillChange;

class Consumer implements Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var bool
     */
    private $isValid = true;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     * @param callable|null   $preLoopCallback Callback that should be called on client before enter a loop.
     */
    public function __construct(ClientInterface $client, callable $preLoopCallback = null)
    {
        $this->client = $client;

        if (null !== $preLoopCallback) {
            $preLoopCallback($this->client);
        }
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Stops consumer loop executing.
     *
     * @param  bool $dropConnection
     * @return void
     */
    public function stop(bool $dropConnection = false): void
    {
        $this->isValid = false;

        if ($dropConnection) {
            $this->client->disconnect();
        }
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function current(): ?PushResponseInterface
    {
        return $this->getPushNotification();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        if ($this->valid()) {
            ++$this->position;
        }

        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->isValid;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        // NOOP
    }

    /**
     * Reads line from connection and returns push response or null on any other type.
     *
     * @return PushResponseInterface|null
     */
    protected function getPushNotification(): ?PushResponseInterface
    {
        $response = $this->client->getConnection()->read();

        return ($response instanceof PushResponse) ? $response : null;
    }
}
