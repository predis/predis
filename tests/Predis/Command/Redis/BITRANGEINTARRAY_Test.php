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
 * @group realm-string
 */
class BITRANGEINTARRAY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\BITRANGEINTARRAY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BITRANGEINTARRAY';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = [1, 10, 16];

        $this->assertSame($raw, $this->getCommand()->parseResponse($raw));
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
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'ARG4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testReturnsOffsetsOfSetBits(): void
    {
        $redis = $this->getClient();

        foreach ([1, 10, 16, 22, 32] as $offset) {
            $redis->setbit('key', $offset, 1);
        }

        $this->assertSame([1, 10, 16, 22, 32], $redis->bitrangeintarray('key', 0, -1));
        $this->assertSame([1, 10], $redis->bitrangeintarray('key', 0, 1, 'byte'));
        $this->assertSame([1, 10], $redis->bitrangeintarray('key', 0, 10, 'bit'));
        $this->assertSame([1, 10], $redis->bitrangeintarray('key', 0, -1, 'byte', 2));
        $this->assertSame([0, 2, 3, 4, 5, 6, 7], $redis->bitrangeintarray('key', 0, 0, 'byte', null, true));
        $this->assertSame([0, 2], $redis->bitrangeintarray('key', 0, 10, 'bit', 2, true));
        $this->assertSame([], $redis->bitrangeintarray('missing', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testReturnsOffsetsOfSetBitsResp3(): void
    {
        $redis = $this->getResp3Client();

        foreach ([1, 10, 16, 22, 32] as $offset) {
            $redis->setbit('key', $offset, 1);
        }

        $this->assertSame([1, 10, 16, 22, 32], $redis->bitrangeintarray('key', 0, -1));
        $this->assertSame([0, 2], $redis->bitrangeintarray('key', 0, 10, 'bit', 2, true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('key', 'list');
        $redis->bitrangeintarray('key', 0, -1);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['key', 0, -1],
                ['key', 0, -1],
            ],
            'with BYTE unit' => [
                ['key', 0, -1, 'byte'],
                ['key', 0, -1, 'BYTE'],
            ],
            'with BIT unit' => [
                ['key', 0, 10, 'bit'],
                ['key', 0, 10, 'BIT'],
            ],
            'with LIMIT' => [
                ['key', 0, -1, 'byte', 5],
                ['key', 0, -1, 'BYTE', 'LIMIT', 5],
            ],
            'with ZEROS' => [
                ['key', 0, -1, 'byte', null, true],
                ['key', 0, -1, 'BYTE', 'ZEROS'],
            ],
            'with all arguments' => [
                ['key', 0, 10, 'bit', 5, true],
                ['key', 0, 10, 'BIT', 'LIMIT', 5, 'ZEROS'],
            ],
            'with LIMIT and ZEROS without unit' => [
                ['key', 0, 10, null, 5, true],
                ['key', 0, 10, 'LIMIT', 5, 'ZEROS'],
            ],
        ];
    }
}
