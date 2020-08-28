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
 * @group realm-hash
 */
class HDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HDEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'field1', 'field2', 'field3');
        $expected = array('key', 'field1', 'field2', 'field3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsFieldsAsSingleArray(): void
    {
        $arguments = array('key', array('field1', 'field2', 'field3'));
        $expected = array('key', 'field1', 'field2', 'field3');

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
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDeletesSpecifiedFieldsFromHash(): void
    {
        $redis = $this->getClient();

        $redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo', 'lol', 'wut');

        $this->assertSame(2, $redis->hdel('metavars', 'foo', 'hoge'));
        $this->assertSame(0, $redis->hdel('metavars', 'foofoo'));
        $this->assertSame(0, $redis->hdel('unknown', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->hdel('foo', 'bar');
    }
}
