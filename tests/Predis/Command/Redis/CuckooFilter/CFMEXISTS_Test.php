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
class CFMEXISTS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFMEXISTS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFMEXISTS';
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
    public function testExistsReturnsExistingItemsWithinCuckooFilter(): void
    {
        $redis = $this->getClient();

        $this->assertSame([0, 0, 0], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
        $redis->cfadd('key', 'item1');
        $this->assertSame([1, 0, 0], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
        $redis->cfadd('key', 'item2');
        $redis->cfadd('key', 'item3');
        $this->assertSame([1, 1, 1], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testExistsReturnsExistingItemsWithinCuckooFilterResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame([false, false, false], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
        $redis->cfadd('key', 'item1');
        $this->assertSame([true, false, false], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
        $redis->cfadd('key', 'item2');
        $redis->cfadd('key', 'item3');
        $this->assertSame([true, true, true], $redis->cfmexists('key', 'item1', 'item2', 'item3'));
    }
}
