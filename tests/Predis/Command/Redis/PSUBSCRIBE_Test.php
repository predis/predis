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
class PSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PSUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel:foo:*', 'channel:hoge:*'];
        $expected = ['channel:foo:*', 'channel:hoge:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['channel:foo:*', 'channel:hoge:*']];
        $expected = ['channel:foo:*', 'channel:hoge:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['psubscribe', 'channel:*', 1];
        $expected = ['psubscribe', 'channel:*', 1];

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
    public function testReturnsTheFirstPsubscribedChannelDetails(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['psubscribe', 'channel:*', 1], $redis->psubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsTheFirstPsubscribedChannelDetailsResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = new PushResponse(['psubscribe', 'channel:*', 1]);

        $this->assertEquals($expectedResponse, $redis->psubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['psubscribe', 'channel:foo:*', 1], $redis->psubscribe('channel:foo:*'));
        $this->assertSame(['psubscribe', 'channel:hoge:*', 2], $redis->psubscribe('channel:hoge:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendSubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['psubscribe', 'channel:foo:*', 1], $redis->psubscribe('channel:foo:*'));
        $this->assertSame(['subscribe', 'channel:foo:bar', 2], $redis->subscribe('channel:foo:bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendUnsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['psubscribe', 'channel:foo:*', 1], $redis->psubscribe('channel:foo:*'));
        $this->assertSame(['psubscribe', 'channel:hoge:*', 2], $redis->psubscribe('channel:hoge:*'));
        $this->assertSame(['unsubscribe', 'channel:foo:bar', 2], $redis->unsubscribe('channel:foo:bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPunsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['psubscribe', 'channel:foo:*', 1], $redis->psubscribe('channel:foo:*'));
        $this->assertSame(['psubscribe', 'channel:hoge:*', 2], $redis->psubscribe('channel:hoge:*'));
        $this->assertSame(['punsubscribe', 'channel:*:*', 2], $redis->punsubscribe('channel:*:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendQuitAfterPsubscribe(): void
    {
        $redis = $this->getClient();
        $quit = $this->getCommandFactory()->create('quit');

        $this->assertSame(['subscribe', 'channel1', 1], $redis->subscribe('channel1'));
        $this->assertEquals('OK', $redis->executeCommand($quit));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCannotSendOtherCommandsAfterPsubscribe(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/ERR.*only .* allowed in this context/');

        $redis = $this->getClient();

        $redis->psubscribe('channel:*');
        $redis->set('foo', 'bar');
    }
}
