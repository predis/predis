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

use Predis\Consumer\AbstractDispatcherLoop;

class DispatcherLoop extends AbstractDispatcherLoop
{
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * {@inheritDoc}
     */
    public function run(): void
    {
        foreach ($this->consumer as $notification) {
            if (null !== $notification) {
                $messageType = $notification->getDataType();

                if (isset($this->callbacksDictionary[$messageType])) {
                    $callback = $this->callbacksDictionary[$messageType];
                    $callback($notification->getPayload(), $this);
                } elseif (isset($this->defaultCallback)) {
                    $callback = $this->defaultCallback;
                    $callback($notification->getPayload(), $this);
                }
            }
        }
    }
}
