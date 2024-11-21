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
 * @group realm-string
 */
class GET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['foo'];
        $expected = ['foo'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('bar', $this->getCommand()->parseResponse('bar'));
    }

    /**
     * @group connected
     */
    public function testReturnsStringValue(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertEquals('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsStringValueUsingCluster(): void
    {
        $this->testReturnsStringValue();
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyStringOnEmptyStrings(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', '');

        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame('', $redis->get('foo'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsEmptyStringOnEmptyStringsUsingCluster(): void
    {
        $this->testReturnsEmptyStringOnEmptyStrings();
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->exists('foo'));
        $this->assertNull($redis->get('foo'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsNullOnNonExistingKeysUsingCluster(): void
    {
        $this->testReturnsNullOnNonExistingKeys();
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->rpush('metavars', 'foo');
        $redis->get('metavars');
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
