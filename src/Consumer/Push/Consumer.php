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

namespace Predis\Consumer\Push;

use Predis\ClientInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Consumer\AbstractConsumer;

class Consumer extends AbstractConsumer
{
    /**
     * @param ClientInterface $client
     * @param callable|null   $preLoopCallback Callback that should be called on client before enter a loop.
     */
    public function __construct(ClientInterface $client, ?callable $preLoopCallback = null)
    {
        parent::__construct($client);

        if (null !== $preLoopCallback) {
            $preLoopCallback($this->client);
        }
    }

    /**
     * @return PushResponseInterface|null
     */
    public function current(): ?PushResponseInterface
    {
        return parent::current();
    }

    /**
     * Reads line from connection and returns push response or null on any other type.
     *
     * @return PushResponseInterface|null
     */
    protected function getValue(): ?PushResponseInterface
    {
        /** @var NodeConnectionInterface $connection */
        $connection = $this->client->getConnection();
        $response = $connection->read();

        return ($response instanceof PushResponse) ? $response : null;
    }
}
