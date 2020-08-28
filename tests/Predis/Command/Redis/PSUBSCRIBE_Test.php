<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-pubsub
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
        $arguments = array('channel:foo:*', 'channel:hoge:*');
        $expected = array('channel:foo:*', 'channel:hoge:*');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = array(array('channel:foo:*', 'channel:hoge:*'));
        $expected = array('channel:foo:*', 'channel:hoge:*');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('psubscribe', 'channel:*', 1);
        $expected = array('psubscribe', 'channel:*', 1);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsTheFirstPsubscribedChannelDetails(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('psubscribe', 'channel:*', 1), $redis->psubscribe('channel:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('psubscribe', 'channel:foo:*', 1), $redis->psubscribe('channel:foo:*'));
        $this->assertSame(array('psubscribe', 'channel:hoge:*', 2), $redis->psubscribe('channel:hoge:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendSubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('psubscribe', 'channel:foo:*', 1), $redis->psubscribe('channel:foo:*'));
        $this->assertSame(array('subscribe', 'channel:foo:bar', 2), $redis->subscribe('channel:foo:bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendUnsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('psubscribe', 'channel:foo:*', 1), $redis->psubscribe('channel:foo:*'));
        $this->assertSame(array('psubscribe', 'channel:hoge:*', 2), $redis->psubscribe('channel:hoge:*'));
        $this->assertSame(array('unsubscribe', 'channel:foo:bar', 2), $redis->unsubscribe('channel:foo:bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendPunsubscribeAfterPsubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('psubscribe', 'channel:foo:*', 1), $redis->psubscribe('channel:foo:*'));
        $this->assertSame(array('psubscribe', 'channel:hoge:*', 2), $redis->psubscribe('channel:hoge:*'));
        $this->assertSame(array('punsubscribe', 'channel:*:*', 2), $redis->punsubscribe('channel:*:*'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCanSendQuitAfterPsubscribe(): void
    {
        $redis = $this->getClient();
        $quit = $this->getCommandFactory()->create('quit');

        $this->assertSame(array('subscribe', 'channel1', 1), $redis->subscribe('channel1'));
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
