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
class LPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LPOP';
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
        $this->assertSame('element', $this->getCommand()->parseResponse('element'));
    }

    /**
     * @group connected
     */
    public function testPopsTheFirstElementFromList(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd');

        $this->assertSame('a', $redis->lpop('letters'));
        $this->assertSame('b', $redis->lpop('letters'));
        $this->assertSame(array('c', 'd'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnEmptyList(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->lpop('letters'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->lpop('foo');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2
     */
    public function testPopsSpecifiedNumberOfElements(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f');

        $this->assertSame(array('a', 'b'), $redis->lpop('letters', 2));
        $this->assertSame(array('c', 'd'), $redis->lpop('letters', 2));
        $this->assertSame(array('e', 'f'), $redis->lrange('letters', 0, -1));
    }
}
