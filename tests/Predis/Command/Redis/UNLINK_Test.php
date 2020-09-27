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
 * @group realm-key
 */
class UNLINK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\UNLINK';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'UNLINK';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key1', 'key2', 'key3');
        $expected = array('key1', 'key2', 'key3');

        $command = $this->getCommand();
        $command->setArguments($arguments);
        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = array(array('key1', 'key2', 'key3'));
        $expected = array('key1', 'key2', 'key3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(10, $command->parseResponse(10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 4.0.0
     */
    public function testReturnsNumberOfDeletedKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo', 'hoge'));

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');
        $this->assertSame(2, $redis->unlink('foo', 'hoge'));
    }
}
