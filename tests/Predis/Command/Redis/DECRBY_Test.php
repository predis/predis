<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

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
        $arguments = ['key', 5];
        $expected = ['key', 5];

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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
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
     * @requiresRedisVersion >= 6.0.0
     */
    public function testCreatesNewKeyOnNonExistingKeyResp3(): void
    {
        $redis = $this->getResp3Client();

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
