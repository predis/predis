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
class LSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 0, 'value');
        $expected = array('key', 0, 'value');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testSetsElementAtSpecifiedIndex(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');

        $this->assertEquals('OK', $redis->lset('letters', 1, 'B'));
        $this->assertSame(array('a', 'B', 'c'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnIndexOutOfRange(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR index out of range');

        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');
        $redis->lset('letters', 21, 'z');
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
        $redis->lset('metavars', 0, 'hoge');
    }
}
