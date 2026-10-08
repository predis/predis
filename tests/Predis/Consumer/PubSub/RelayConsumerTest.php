<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Consumer\PubSub;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\Connection\Parameters;
use Predis\Connection\RelayConnection;
use Relay\Relay;

/**
 * @group ext-relay
 * @requires extension relay
 */
class RelayConsumerTest extends TestCase
{
    /**
     * @group disconnected
     */
    public function testSubscribeAcceptsCallbackAndDispatchesMessages(): void
    {
        $relay = $this->getMockBuilder(Relay::class)
            ->onlyMethods(['isConnected', 'subscribe', 'close'])
            ->getMock();
        $relay->method('isConnected')->willReturn(true);
        $relay->expects($this->once())
            ->method('subscribe')
            ->with(['notifications', 'control'], $this->isType('callable'))
            ->willReturnCallback(static function (array $channels, callable $callback) use ($relay) {
                $callback($relay, 'notifications', null);
                $callback($relay, 'notifications', 'Make it so.');

                return true;
            });

        $client = new Client(new RelayConnection(new Parameters(), $relay));
        $consumer = $client->pubSubLoop();
        $messages = [];

        $consumer->subscribe('notifications', 'control', function ($message, $callbackClient) use (&$messages, $relay) {
            $this->assertSame($relay, $callbackClient);
            $messages[] = $message;
        });

        $this->assertEquals([
            (object) ['kind' => 'subscribe', 'channel' => 'notifications', 'payload' => null],
            (object) ['kind' => 'message', 'channel' => 'notifications', 'payload' => 'Make it so.'],
        ], $messages);
        $this->assertFalse($consumer->valid());
    }
}
