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
class HSTRLEN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HSTRLEN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HSTRLEN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'field');
        $expected = array('key', 'field');

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

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
        $this->assertSame(10, $command->parseResponse(10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testReturnsStringLengthOfSpecifiedField(): void
    {
        $redis = $this->getClient();

        $redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo');

        // Existing key and field
        $this->assertSame(3, $redis->hstrlen('metavars', 'foo'));
        $this->assertSame(4, $redis->hstrlen('metavars', 'hoge'));

        // Existing key but non existing field
        $this->assertSame(0, $redis->hstrlen('metavars', 'foofoo'));

        // Non existing key
        $this->assertSame(0, $redis->hstrlen('unknown', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->hstrlen('metavars', 'foo');
    }
}
