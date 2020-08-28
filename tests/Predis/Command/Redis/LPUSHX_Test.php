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
 * @group realm-list
 */
class LPUSHX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LPUSHX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LPUSHX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'value');
        $expected = array('key', 'value');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testPushesElementsToHeadOfExistingList(): void
    {
        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');

        $this->assertSame(2, $redis->lpushx('metavars', 'hoge'));
        $this->assertSame(array('hoge', 'foo'), $redis->lrange('metavars', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testDoesNotPushElementOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->lpushx('metavars', 'foo'));
        $this->assertSame(0, $redis->lpushx('metavars', 'hoge'));
        $this->assertSame(0, $redis->exists('metavars'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lpushx('metavars', 'hoge');
    }
}
