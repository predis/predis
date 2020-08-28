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
 * @group realm-string
 */
class INCRBYFLOAT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\INCRBYFLOAT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'INCRBYFLOAT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 5.0);
        $expected = array('key', 5.0);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(5.0, $this->getCommand()->parseResponse(5.0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCreatesNewKeyOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertEquals(10.5, $redis->incrbyfloat('foo', 10.5));
        $this->assertEquals(10.5, $redis->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsTheValueOfTheKeyAfterIncrement(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 2);

        // We use round() to avoid errors on some platforms, see the following
        // issue https://github.com/predis/predis/issues/220 for reference.
        $this->assertEquals(22.123, $redis->incrbyfloat('foo', 20.123));
        $this->assertEquals(10, round($redis->incrbyfloat('foo', -12.123), 5));
        $this->assertEquals(-100.01, round($redis->incrbyfloat('foo', -110.01), 5));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnDecrementValueNotFloat(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not a valid float');

        $redis = $this->getClient();

        $redis->incrbyfloat('foo', 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnKeyValueNotFloat(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not a valid float');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->incrbyfloat('foo', 10.0);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->incrbyfloat('metavars', 10.0);
    }
}
