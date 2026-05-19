<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-array
 */
class ARSEEK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARSEEK::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARSEEK';
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
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 5];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 5];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsCursorAndSubsequentInsertWritesAtPosition(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a', 'b', 'c');

        $this->assertSame(1, $redis->arseek('arr', 10));
        $this->assertSame(10, $redis->arinsert('arr', 'x'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsZeroOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->arseek('nonexistent', 5));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNextReflectsNewCursorPosition(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a');
        $redis->arseek('arr', 7);

        $this->assertSame(7, $redis->arnext('arr'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsCursorResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arinsert('arr', 'a');

        $this->assertSame(1, $redis->arseek('arr', 5));
        $this->assertSame(0, $redis->arseek('nonexistent', 5));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->arseek('foo', 5);
    }
}
