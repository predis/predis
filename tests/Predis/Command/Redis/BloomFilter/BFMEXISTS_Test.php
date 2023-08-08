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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class BFMEXISTS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BFMEXISTS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BFMEXISTS';
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
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testExistsReturnsExistingItemsWithinBloomFilter(): void
    {
        $redis = $this->getClient();

        $redis->bfmadd('key', 'item1', 'item2');
        $this->assertSame([1, 1], $redis->bfmexists('key', 'item1', 'item2'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testExistsReturnsExistingItemsWithinBloomFilterResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->bfmadd('key', 'item1', 'item2');
        $this->assertSame([true, true], $redis->bfmexists('key', 'item1', 'item2'));
    }
}
