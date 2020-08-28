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
class TYPE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\TYPE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'TYPE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key');
        $expected = array('key');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('none', $this->getCommand()->parseResponse('none'));
    }

    /**
     * @group connected
     */
    public function testReturnsTypeOfKey(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('none', $redis->type('type:keydoesnotexist'));

        $redis->set('type:string', 'foobar');
        $this->assertEquals('string', $redis->type('type:string'));

        $redis->lpush('type:list', 'foobar');
        $this->assertEquals('list', $redis->type('type:list'));

        $redis->sadd('type:set', 'foobar');
        $this->assertEquals('set', $redis->type('type:set'));

        $redis->zadd('type:zset', 0, 'foobar');
        $this->assertEquals('zset', $redis->type('type:zset'));

        $redis->hset('type:hash', 'foo', 'bar');
        $this->assertEquals('hash', $redis->type('type:hash'));
    }
}
