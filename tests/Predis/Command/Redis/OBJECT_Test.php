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
 * @group realm-server
 */
class OBJECT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\OBJECT_';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'OBJECT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('REFCOUNT', 'key');
        $expected = array('REFCOUNT', 'key');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('ziplist', $this->getCommand()->parseResponse('ziplist'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.3
     */
    public function testObjectRefcount(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $this->assertIsInt($redis->object('REFCOUNT', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.3
     */
    public function testObjectIdletime(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $this->assertIsInt($redis->object('IDLETIME', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.3
     */
    public function testObjectEncoding(): void
    {
        $redis = $this->getClient();

        $redis->lpush('list:metavars', 'foo', 'bar');
        $this->assertMatchesRegularExpression('/[zip|quick]list/', $redis->object('ENCODING', 'list:metavars'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.3
     */
    public function testReturnsNullOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->object('REFCOUNT', 'foo'));
        $this->assertNull($redis->object('IDLETIME', 'foo'));
        $this->assertNull($redis->object('ENCODING', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.3
     */
    public function testThrowsExceptionOnInvalidSubcommand(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->object('INVALID', 'foo');
    }
}
