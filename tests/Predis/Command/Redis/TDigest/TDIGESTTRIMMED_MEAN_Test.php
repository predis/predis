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
class TDIGESTTRIMMED_MEAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTTRIMMED_MEAN::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTTRIMMED_MEAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 0.1, 0.2];
        $expectedArguments = ['key', 0.1, 0.2];

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
     * @param  array  $addArguments
     * @param  string $key
     * @param  array  $trimmedMeanArguments
     * @param  string $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testReturnsMeanValueWithinGivenQuantilesRange(
        array $addArguments,
        string $key,
        array $trimmedMeanArguments,
        string $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->tdigestcreate($key);
        $redis->tdigestadd(...$addArguments);

        $actualResponse = $redis->tdigesttrimmed_mean(...$trimmedMeanArguments);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReturnsMeanValueWithinGivenQuantilesRangeResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->tdigestcreate('key');
        $redis->tdigestadd('key', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $actualResponse = $redis->tdigesttrimmed_mean('key', 0.1, 0.6);

        $this->assertEquals(4.0, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testReturnsNanOnEmptySketchKey(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('key');

        $actualResponse = $redis->tdigesttrimmed_mean('key', 0, 1);
        $this->assertEquals('nan', $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testThrowsExceptionOnNonExistingTDigestSketch(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR T-Digest: key does not exist');

        $redis->tdigesttrimmed_mean('key', 0, 1);
    }

    public function sketchesProvider(): array
    {
        return [
            'between low and high cut' => [
                ['key', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'key',
                ['key', 0.1, 0.6],
                '4',
            ],
            'without low cut' => [
                ['key', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'key',
                ['key', 0, 0.6],
                '3.5',
            ],
            'without high cut' => [
                ['key', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'key',
                ['key', 0.6, 1],
                '8.5',
            ],
            'without low and high cut' => [
                ['key', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'key',
                ['key', 0, 1],
                '5.5',
            ],
        ];
    }
}
