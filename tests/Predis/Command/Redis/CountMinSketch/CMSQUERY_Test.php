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

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CMSQUERY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSQUERY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSQUERY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item1', 'item2'];
        $expectedArguments = ['key', 'item1', 'item2'];

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
     * @param  array $queryArguments
     * @param  array $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testReturnCountValuesForGivenItemsWithinCountMinSketch(
        array $queryArguments,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->cmsinitbydim('key', 2000, 7);

        $actualResponse = $redis->cmsquery(...$queryArguments);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReturnCountValuesForGivenItemsWithinCountMinSketchResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->cmsinitbydim('key', 2000, 7);

        $actualResponse = $redis->cmsquery('key', 'item1');
        $this->assertSame([0], $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key does not exist');

        $redis = $this->getClient();

        $redis->cmsquery('cmsquery_foo', 'item1');
    }

    public function sketchesProvider(): array
    {
        return [
            'with single item' => [
                ['key', 'item1'],
                [0],
            ],
            'with multiple items' => [
                ['key', 'item1', 'item2'],
                [0, 0],
            ],
        ];
    }
}
