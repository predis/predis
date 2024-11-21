<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
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
class CFDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFDEL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item'];
        $expectedArguments = ['key', 'item'];

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
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testDeletesItemFromGivenCuckooFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('key', 'item');
        $singleItemResponse = $redis->cfdel('key', 'item');

        $this->assertSame(1, $singleItemResponse);
        $this->assertSame(0, $redis->cfexists('key', 'item'));

        $redis->cfadd('key', 'item');
        $redis->cfadd('key', 'item');

        $multipleItemsResponse = $redis->cfdel('key', 'item');
        $this->assertSame(1, $multipleItemsResponse);
        $this->assertSame(1, $redis->cfexists('key', 'item'));

        $nonExistingItemResponse = $redis->cfdel('key', 'non_existing_item');
        $this->assertSame(0, $nonExistingItemResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testDeleteThrowsExceptionOnNonExistingFilterKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Not found');

        $redis->cfdel('non_existing_key', 'item');
    }
}
