<?php

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

class CFADD_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return CFADD::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'CFADD';
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
    public function testAddItemToCuckooFilter(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->cfadd('key', 'item');
        $this->assertSame(1, $actualResponse);
        $this->assertSame(1, $redis->cfexists('key', 'item'));
    }
    /**
     * @group connected
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('cfadd_foo', 'bar');
        $redis->cfadd('cfadd_foo', 'foo');
    }
}
