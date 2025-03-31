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

abstract class AbstractDispatcherLoop implements DispatcherLoopInterface
{
    /**
     * @var ConsumerInterface
     */
    protected $consumer;

    /**
     * @var callable|null
     */
    protected $defaultCallback;

    /**
     * @var callable[]
     */
    protected $callbacksDictionary;

    /**
     * {@inheritDoc}
     */
    public function __construct(ConsumerInterface $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsumer(): ConsumerInterface
    {
        return $this->consumer;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultCallback(?callable $callback = null): void
    {
        $this->defaultCallback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function attachCallback(string $messageType, callable $callback): void
    {
        $this->callbacksDictionary[$messageType] = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function detachCallback(string $messageType): void
    {
        if (isset($this->callbacksDictionary[$messageType])) {
            unset($this->callbacksDictionary[$messageType]);
        }
    }

    /**
     * {@inheritDoc}
     */
    abstract public function run(): void;

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        $this->consumer->stop();
    }
}
