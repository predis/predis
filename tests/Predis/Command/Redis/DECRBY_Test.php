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
class DECRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\DECRBY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'DECRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 5);
        $expected = array('key', 5);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(5, $this->getCommand()->parseResponse(5));
    }

    /**
     * @group connected
     */
    public function testCreatesNewKeyOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(-10, $redis->decrby('foo', 10));
        $this->assertEquals(-10, $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsTheValueOfTheKeyAfterDecrement(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 10);

        $this->assertSame(6, $redis->decrby('foo', 4));
        $this->assertSame(0, $redis->decrby('foo', 6));
        $this->assertSame(-25, $redis->decrby('foo', 25));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnDecrementValueNotInteger(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $redis = $this->getClient();

        $redis->decrby('foo', 'bar');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnKeyValueNotInteger(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->decrby('foo', 5);
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->decrby('metavars', 10);
    }
}
