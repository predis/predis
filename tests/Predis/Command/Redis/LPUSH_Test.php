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
class LPUSH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LPUSH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LPUSH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'value1', 'value2', 'value3');
        $expected = array('key', 'value1', 'value2', 'value3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsValuesAsSingleArray(): void
    {
        $arguments = array('key', array('value1', 'value2', 'value3'));
        $expected = array('key', 'value1', 'value2', 'value3');

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
     */
    public function testPushesElementsToHeadOfList(): void
    {
        $redis = $this->getClient();

        // NOTE: List push operations return the list length since Redis commit 520b5a3
        $this->assertSame(1, $redis->lpush('metavars', 'foo'));
        $this->assertSame(2, $redis->lpush('metavars', 'hoge'));
        $this->assertSame(array('hoge', 'foo'), $redis->lrange('metavars', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lpush('metavars', 'hoge');
    }
}
