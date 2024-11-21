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
class HGETALL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HGETALL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HGETALL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['foo', 'bar', 'hoge', 'piyo', 'lol', 'wut'];
        $expected = ['foo' => 'bar', 'hoge' => 'piyo', 'lol' => 'wut'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsAllTheFieldsAndTheirValues(): void
    {
        $redis = $this->getClient();

        $redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo', 'lol', 'wut');

        $this->assertSame(['foo' => 'bar', 'hoge' => 'piyo', 'lol' => 'wut'], $redis->hgetall('metavars'));
        $this->assertSame([], $redis->hgetall('unknown'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsAllTheFieldsAndTheirValuesUsingCluster(): void
    {
        $redis = $this->getClient();

        $redis->del('metavars');

        $this->testReturnsAllTheFieldsAndTheirValues();
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
        $redis->hgetall('foo');
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
