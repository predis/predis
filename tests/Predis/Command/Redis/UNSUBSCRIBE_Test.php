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
class UNSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\UNSUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'UNSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('channel1', 'channel2', 'channel3');
        $expected = array('channel1', 'channel2', 'channel3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = array(array('channel1', 'channel2', 'channel3'));
        $expected = array('channel1', 'channel2', 'channel3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('unsubscribe', 'channel', 1);
        $expected = array('unsubscribe', 'channel', 1);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDoesNotSwitchToSubscribeMode(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('unsubscribe', 'channel', 0), $redis->unsubscribe('channel'));
        $this->assertSame('echoed', $redis->echo('echoed'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromNotSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('unsubscribe', 'channel', 0), $redis->unsubscribe('channel'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('subscribe', 'channel', 1), $redis->subscribe('channel'));
        $this->assertSame(array('unsubscribe', 'channel', 0), $redis->unsubscribe('channel'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromAllSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array('subscribe', 'channel:foo', 1), $redis->subscribe('channel:foo'));
        $this->assertSame(array('subscribe', 'channel:bar', 2), $redis->subscribe('channel:bar'));

        list($_, $unsubscribed1, $_) = $redis->unsubscribe();
        list($_, $unsubscribed2, $_) = $redis->getConnection()->read();
        $this->assertSameValues(array('channel:foo', 'channel:bar'), array($unsubscribed1, $unsubscribed2));

        $this->assertSame('echoed', $redis->echo('echoed'));
    }
}
