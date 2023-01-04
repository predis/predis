<?php

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;

class CFEXISTS_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return CFEXISTS::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'CFEXISTS';
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
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testExistsReturnsExistingItemWithinCuckooFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('key', 'item');

        $this->assertSame(1, $redis->cfexists('key', 'item'));
        $this->assertSame(0, $redis->cfexists('non-existing key', 'item'));
    }
}
