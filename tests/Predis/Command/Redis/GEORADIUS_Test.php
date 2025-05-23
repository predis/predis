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
 * @group realm-geospatial
 */
class GEORADIUS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GEORADIUS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GEORADIUS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = [
            'Sicily', 15, 37, 200, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        ];

        $expected = [
            'Sicily', 15, 37, 200, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        ];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithComplexOptions(): void
    {
        $arguments = [
            'Sicily', 15, 37, 200, 'km', [
                'store' => 'key:store',
                'storedist' => 'key:storedist',
                'withdist' => true,
                'withcoord' => true,
                'withhash' => true,
                'count' => 1,
                'sort' => 'asc',
            ],
        ];

        $expected = [
            'Sicily', 15, 37, 200, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        ];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithSpecificOptionsSetToFalse(): void
    {
        $arguments = [
            'Sicily', 15, 37, 200, 'km', [
                'store' => 'key:store',
                'storedist' => 'key:storedist',
                'withdist' => false,
                'withcoord' => false,
                'withhash' => false,
                'count' => 1,
                'sort' => 'asc',
            ],
        ];

        $expected = ['Sicily', 15, 37, 200, 'km', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponseWithNoOptions(): void
    {
        $raw = [
            ['Palermo', '190.4424'],
            ['Catania', '56.4413'],
        ];

        $expected = [
            ['Palermo', '190.4424'],
            ['Catania', '56.4413'],
        ];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @dataProvider prefixKeysProvider
     * @group disconnected
     */
    public function testPrefixKeys(array $actualArguments, array $expectedArguments): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $prefix = 'prefix:';

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoRadiusInfoWithNoOptions(): void
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEquals(['Palermo', 'Catania'], $redis->georadius('Sicily', 15, 37, 200, 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testCommandReturnsGeoRadiusInfoWithNoOptionsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEquals(['Palermo', 'Catania'], $redis->georadius('Sicily', 15, 37, 200, 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoRadiusInfoWithOptions(): void
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEqualsWithDelta([
            ['Palermo', '190.4424', [13.36138933897018433, 38.11555639549629859]],
            ['Catania', '56.4413', [15.08726745843887329, 37.50266842333162032]],
        ], $redis->georadius('Sicily', 15, 37, 200, 'km', 'WITHDIST', 'WITHCOORD'), 0.1);
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

        $redis->lpush('Sicily', 'Palermo');
        $redis->georadius('Sicily', 15, 37, 200, 'km');
    }

    public function prefixKeysProvider(): array
    {
        return [
            'with empty arguments' => [
                [],
                [],
            ],
            'with key argument only' => [
                ['key'],
                ['prefix:key'],
            ],
            'with key and STORE arguments' => [
                ['key', 'arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'STORE', 'key'],
                ['prefix:key', 'arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'STORE', 'prefix:key'],
            ],
            'with key and STOREDIST arguments' => [
                ['key', 'arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'STOREDIST', 'key'],
                ['prefix:key', 'arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'STOREDIST', 'prefix:key'],
            ],
        ];
    }
}
