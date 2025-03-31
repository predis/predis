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

namespace Predis\Consumer;

use Predis\ClientInterface;
use ReturnTypeWillChange;

abstract class AbstractConsumer implements ConsumerInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var bool
     */
    protected $isValid = true;

    /**
     * @var int
     */
    protected $position = 0;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function stop(bool $drop = false): bool
    {
        $this->isValid = false;

        if ($drop) {
            $this->client->disconnect();

            return true;
        }

        return true;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->getValue();
    }

    /**
     * Returns last message from server.
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    abstract protected function getValue();

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->isValid;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if ($this->valid()) {
            ++$this->position;
        }
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
    public function rewind()
    {
        // NOOP
    }
}
