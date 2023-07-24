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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;
use Predis\Consumer\Push\PushResponse;

/**
 * @group commands
 * @group realm-pubsub
 * @group relay-incompatible
 */
class PUNSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PUNSUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PUNSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel:foo:*', 'channel:bar:*'];
        $expected = ['channel:foo:*', 'channel:bar:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['channel:foo:*', 'channel:bar:*']];
        $expected = ['channel:foo:*', 'channel:bar:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['punsubscribe', 'channel:*', 1];
        $expected = ['punsubscribe', 'channel:*', 1];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDoesNotSwitchToSubscribeMode(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['punsubscribe', 'channel:*', 0], $redis->punsubscribe('channel:*'));
        $this->assertSame('echoed', $redis->echo('echoed'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testDoesNotSwitchToSubscribeModeResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = new PushResponse(['punsubscribe', 'channel:*', 0]);

        $this->assertEquals($expectedResponse, $redis->punsubscribe('channel:*'));
        $this->assertSame('echoed', $redis->echo('echoed'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromNotSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['punsubscribe', 'channel:*', 0], $redis->punsubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));
        $this->assertSame(['punsubscribe', 'channel:*', 2], $redis->punsubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testUnsubscribesFromAllSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));
        $this->assertSame(['punsubscribe', null, 2], $redis->punsubscribe());
    }
}
