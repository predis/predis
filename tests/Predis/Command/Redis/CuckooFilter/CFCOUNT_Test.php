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

/**
 * @group commands
 * @group realm-stack
 */
class CFCOUNT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFCOUNT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFCOUNT';
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
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testReturnsCountOfItemsWithinCuckooFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('key', 'item');

        $singleItemResponse = $redis->cfcount('key', 'item');
        $this->assertSame(1, $singleItemResponse);

        $redis->cfadd('key', 'item');
        $redis->cfadd('key', 'item');

        $multipleItemsResponse = $redis->cfcount('key', 'item');
        $this->assertSame(3, $multipleItemsResponse);

        $nonExistingItemResponse = $redis->cfcount('non_existing_key', 'item');
        $this->assertSame(0, $nonExistingItemResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReturnsCountOfItemsWithinCuckooFilterResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->cfadd('key', 'item');

        $singleItemResponse = $redis->cfcount('key', 'item');
        $this->assertSame(1, $singleItemResponse);

        $redis->cfadd('key', 'item');
        $redis->cfadd('key', 'item');

        $multipleItemsResponse = $redis->cfcount('key', 'item');
        $this->assertSame(3, $multipleItemsResponse);

        $nonExistingItemResponse = $redis->cfcount('non_existing_key', 'item');
        $this->assertSame(0, $nonExistingItemResponse);
    }
}
