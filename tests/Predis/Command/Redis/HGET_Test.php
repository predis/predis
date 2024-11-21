<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-hash
 */
class HGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HGET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HGET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'field'];
        $expected = ['key', 'field'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('value', $this->getCommand()->parseResponse('value'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsValueOfSpecifiedField(): void
    {
        $redis = $this->getClient();

        $redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo');

        $this->assertSame('bar', $redis->hget('metavars', 'foo'));
        $this->assertNull($redis->hget('metavars', 'lol'));
        $this->assertNull($redis->hget('unknown', 'foo'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsValueOfSpecifiedFieldUsingCluster(): void
    {
        $redis = $this->getClient();

        $redis->del('metavars');

        $this->testReturnsValueOfSpecifiedField();
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/.*Operation against a key holding the wrong kind of value.*/');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->hget('foo', 'bar');
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testThrowsExceptionOnWrongTypeUsingCluster(): void
    {
        $this->testThrowsExceptionOnWrongType();
    }
}
