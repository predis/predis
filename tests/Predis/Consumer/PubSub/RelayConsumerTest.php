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

    /**
     * @group disconnected
     */
    public function testSubscribeAppliesPrefixToChannelsOnly(): void
    {
        $relay = $this->getMockBuilder(Relay::class)
            ->onlyMethods(['isConnected', 'subscribe', 'close'])
            ->getMock();
        $relay->method('isConnected')->willReturn(true);
        $relay->expects($this->once())
            ->method('subscribe')
            ->with(['predis:notifications', 'predis:control'], $this->isType('callable'))
            ->willReturnCallback(static function (array $channels, callable $callback) use ($relay) {
                $callback($relay, 'predis:notifications', 'Make it so.');

                return true;
            });

        $client = new Client(new RelayConnection(new Parameters(), $relay), ['prefix' => 'predis:']);
        $messages = [];

        $client->pubSubLoop()->subscribe('notifications', 'control', static function ($message) use (&$messages) {
            $messages[] = $message;
        });

        $this->assertEquals([
            (object) ['kind' => 'message', 'channel' => 'predis:notifications', 'payload' => 'Make it so.'],
        ], $messages);
    }

    /**
     * @group disconnected
     */
    public function testPsubscribeAppliesPrefixToPatternsOnly(): void
    {
        $relay = $this->getMockBuilder(Relay::class)
            ->onlyMethods(['isConnected', 'psubscribe', 'close'])
            ->getMock();
        $relay->method('isConnected')->willReturn(true);
        $relay->expects($this->once())
            ->method('psubscribe')
            ->with(['predis:notifications.*', 'predis:control.*'], $this->isType('callable'))
            ->willReturnCallback(static function (array $patterns, callable $callback) use ($relay) {
                $callback($relay, 'predis:notifications.*', 'predis:notifications.bridge', 'Make it so.');

                return true;
            });

        $client = new Client(new RelayConnection(new Parameters(), $relay), ['prefix' => 'predis:']);
        $messages = [];

        $client->pubSubLoop()->psubscribe('notifications.*', 'control.*', static function ($message) use (&$messages) {
            $messages[] = $message;
        });

        $this->assertEquals([
            (object) [
                'kind' => 'pmessage',
                'pattern' => 'predis:notifications.*',
                'channel' => 'predis:notifications.bridge',
                'payload' => 'Make it so.',
            ],
        ], $messages);
    }
}
