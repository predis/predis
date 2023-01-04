<?php

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-bloom
 */
class BFEXISTS_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFEXISTS::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BFEXISTS';
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
     * @return void
     * @requiresRedisBfVersion >= 1.0
     */
    public function testExistsReturnsExistingItemWithinBloomFilter(): void
    {
        $redis = $this->getClient();

        $redis->bfadd('key', 'item');

        $this->assertSame(1, $redis->bfexists('key', 'item'));
        $this->assertSame(0, $redis->bfexists('key', 'non-existing'));
    }
}
