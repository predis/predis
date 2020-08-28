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
class HINCRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HINCRBY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HINCRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'field', 10);
        $expected = array('key', 'field', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(10, $this->getCommand()->parseResponse(10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testIncrementsValueOfFieldByInteger(): void
    {
        $redis = $this->getClient();

        $this->assertSame(10, $redis->hincrby('metavars', 'foo', 10));
        $this->assertSame(5, $redis->hincrby('metavars', 'hoge', 5));
        $this->assertSame(15, $redis->hincrby('metavars', 'hoge', 10));
        $this->assertSame(array('foo' => '10', 'hoge' => '15'), $redis->hgetall('metavars'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDecrementsValueOfFieldByInteger(): void
    {
        $redis = $this->getClient();

        $this->assertSame(-10, $redis->hincrby('metavars', 'foo', -10));
        $this->assertSame(-5, $redis->hincrby('metavars', 'hoge', -5));
        $this->assertSame(-15, $redis->hincrby('metavars', 'hoge', -10));
        $this->assertSame(array('foo' => '-10', 'hoge' => '-15'), $redis->hgetall('metavars'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnStringField(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR hash value is not an integer');

        $redis = $this->getClient();

        $redis->hset('metavars', 'foo', 'bar');
        $redis->hincrby('metavars', 'foo', 10);
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
        $redis->hincrby('foo', 'bar', 10);
    }
}
