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

use Predis\NotSupportedException;

/**
 * Relay PUB/SUB consumer.
 */
class RelayConsumer extends Consumer
{
    /**
     * Subscribes to the specified channels.
     *
     * @param string   ...$channel One or more channel names.
     * @param callable $callback   The message callback.
     */
    public function subscribe(string ...$channel) // @phpstan-ignore-line
    {
        $channels = func_get_args();
        $callback = array_pop($channels);

        $this->statusFlags |= self::STATUS_SUBSCRIBED;

        $command = $this->client->createCommand('subscribe', [
            $channels,
            function ($relay, $channel, $message) use ($callback) {
                $callback((object) [
                    'kind' => is_null($message) ? self::SUBSCRIBE : self::MESSAGE,
                    'channel' => $channel,
                    'payload' => $message,
                ], $relay);
            },
        ]);

        $this->client->getConnection()->executeCommand($command);

        $this->invalidate();
    }

    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param string   ...$pattern One or more channel name patterns.
     * @param callable $callback   The message callback.
     */
    public function psubscribe(...$pattern) // @phpstan-ignore-line
    {
        $patterns = func_get_args();
        $callback = array_pop($patterns);

        $this->statusFlags |= self::STATUS_PSUBSCRIBED;

        $command = $this->client->createCommand('psubscribe', [
            $patterns,
            function ($relay, $pattern, $channel, $message) use ($callback) {
                $callback((object) [
                    'kind' => is_null($message) ? self::PSUBSCRIBE : self::PMESSAGE,
                    'pattern' => $pattern,
                    'channel' => $channel,
                    'payload' => $message,
                ], $relay);
            },
        ]);

        $this->client->getConnection()->executeCommand($command);

        $this->invalidate();
    }

    /**
     * {@inheritDoc}
     */
    protected function genericSubscribeInit($subscribeAction)
    {
        if (isset($this->options[$subscribeAction])) {
            throw new NotSupportedException('Relay does not support Pub/Sub constructor options.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ping($payload = null)
    {
        throw new NotSupportedException('Relay does not support PING in Pub/Sub.');
    }

    /**
     * {@inheritDoc}
     */
    public function stop($drop = false): bool
    {
        return false;
    }
}
