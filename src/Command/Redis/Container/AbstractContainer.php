<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Container;

use Predis\ClientInterface;

abstract class AbstractContainer implements ContainerInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function __call(string $subcommandID, array $arguments)
    {
        array_unshift($arguments, strtoupper($subcommandID));

        return $this->client->executeCommand(
            $this->client->createCommand($this->getContainerCommandId(), $arguments)
        );
    }

    abstract public function getContainerCommandId(): string;
}
