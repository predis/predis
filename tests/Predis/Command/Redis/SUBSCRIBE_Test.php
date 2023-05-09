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

/**
 * @group commands
 * @group realm-pubsub
 * @group relay-incompatible
 */
class SUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel:foo', 'channel:bar'];
        $expected = ['channel:foo', 'channel:bar'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['channel:foo', 'channel:bar']];
        $expected = ['channel:foo', 'channel:bar'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['subscribe', 'channel', 1];
        $expected = ['subscribe', 'channel', 1];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsTheFirstSubscribedChannelDetails(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel', 1], $redis->subscribe('channel'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendSubscribeAfterSubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPsubscribeAfterSubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['psubscribe', 'channel:*', 2], $redis->psubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendUnsubscribeAfterSubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));
        $this->assertSame(['unsubscribe', 'channel:foo', 1], $redis->unsubscribe('channel:foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPunsubscribeAfterSubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));
        $this->assertSame(['punsubscribe', 'channel:*', 2], $redis->punsubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendQuitAfterSubscribe(): void
    {
        $redis = $this->getClient();
        $quit = $this->getCommandFactory()->create('quit');

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertEquals('OK', $redis->executeCommand($quit));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCannotSendOtherCommandsAfterSubscribe(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/ERR.*only .* allowed in this context/');

        $redis = $this->getClient();

        $redis->subscribe('channel:foo');
        $redis->set('foo', 'bar');
    }
}
