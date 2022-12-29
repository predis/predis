<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-bloom
 */
class BFMEXISTS_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFMEXISTS::class;
    }

    /**
     * @inheritDoc
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
     * @return void
     * @requiresRedisBfVersion >= 1.0
     */
    public function testExistsReturnsExistingItemsWithinBloomFilter(): void
    {
        $redis = $this->getClient();

        $redis->bfmadd('key', 'item1', 'item2');
        $this->assertSame([1,1], $redis->bfmexists('key', 'item1', 'item2'));
    }
}
