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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CFINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFINFO';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key'];
        $expectedArguments = ['key'];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider responsesProvider
     */
    public function testParseResponse(array $actualResponse, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInfoReturnsInformationAboutGivenCuckooFilter(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            'Size' => 1080,
            'Number of buckets' => 512,
            'Number of filters' => 1,
            'Number of items inserted' => 1,
            'Number of items deleted' => 0,
            'Bucket size' => 2,
            'Expansion rate' => 1,
            'Max iterations' => 20,
        ];

        $redis->cfadd('key', 'item');

        $this->assertSame($expectedResponse, $redis->cfinfo('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testInfoReturnsInformationAboutGivenCuckooFilterResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = [
            'Size' => 1080,
            'Number of buckets' => 512,
            'Number of filters' => 1,
            'Number of items inserted' => 1,
            'Number of items deleted' => 0,
            'Bucket size' => 2,
            'Expansion rate' => 1,
            'Max iterations' => 20,
        ];

        $redis->cfadd('key', 'item');

        $this->assertSame($expectedResponse, $redis->cfinfo('key'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInfoThrowsExceptionOnNonExistingFilterKeyGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR not found');

        $redis->cfinfo('non_existing_key');
    }

    public function responsesProvider(): array
    {
        return [
            'with one modifier' => [
                [100],
                [100],
            ],
            'with all modifiers' => [
                [
                    'Size',
                    100,
                    'Number of buckets',
                    296,
                    'Number of filter',
                    1,
                    'Number of items inserted',
                    1,
                    'Number of items deleted',
                    1,
                    'Bucket size',
                    0,
                    'Expansion rate',
                    1,
                    'Max iteration',
                    20,
                ],
                [
                    'Size' => 100,
                    'Number of buckets' => 296,
                    'Number of filter' => 1,
                    'Number of items inserted' => 1,
                    'Number of items deleted' => 1,
                    'Bucket size' => 0,
                    'Expansion rate' => 1,
                    'Max iteration' => 20,
                ],
            ],
        ];
    }
}
