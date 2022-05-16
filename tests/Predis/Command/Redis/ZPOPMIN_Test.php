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
 * @group realm-zset
 */
class ZPOPMIN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZPOPMIN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZPOPMIN';
    }

    /**
     * @requiresRedisVersion >= 5.0.0
     *
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('zset', 2);
        $expected = array('zset', 2);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @requiresRedisVersion >= 5.0.0
     *
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('element1', '1', 'element2', '2', 'element3', '3');
        $expected = array('element1' => '1', 'element2' => '2', 'element3' => '3');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @requiresRedisVersion >= 5.0.0
     *
     * @group connected
     */
    public function testReturnsElements(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array(), $redis->zpopmin('letters'));
        $this->assertSame(array(), $redis->zpopmin('letters', 3));

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('a' => '-10'), $redis->zpopmin('letters'));
        $this->assertSame(array('b' => '0', 'c' => '10', 'd' => '20'), $redis->zpopmin('letters', 3));
        $this->assertSame(array('e' => '20', 'f' => '30'), $redis->zpopmin('letters', 3));
    }

    /**
     * @requiresRedisVersion >= 5.0.0
     *
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zpopmin('foo');
    }
}
