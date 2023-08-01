<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TDIGESTCREATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTCREATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTCREATE';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @dataProvider sketchesProvider
     * @param  array  $createArguments
     * @param  string $key
     * @param  int    $expectedCompression
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testCreateTDigestSketchWithGivenConfiguration(
        array $createArguments,
        string $key,
        int $expectedCompression
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->tdigestcreate(...$createArguments);
        $info = $redis->tdigestinfo($key);

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame($expectedCompression, $info['Compression']);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testCreateTDigestSketchWithGivenConfigurationResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->tdigestcreate('key');
        $info = $redis->tdigestinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(100, $info['Compression']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testThrowsExceptionOnAlreadyCreatedKey(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->tdigestcreate('key');
        $this->assertEquals('OK', $actualResponse);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR T-Digest: key already exists');

        $redis->tdigestcreate('key');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with 0 compression' => [
                ['key', 0],
                ['key'],
            ],
            'with COMPRESSION modifier' => [
                ['key', 100],
                ['key', 'COMPRESSION', 100],
            ],
        ];
    }

    public function sketchesProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                'key',
                100,
            ],
            'with modified COMPRESSION' => [
                ['key', 120],
                'key',
                120,
            ],
        ];
    }
}
