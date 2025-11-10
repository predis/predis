<?php

namespace Predis\Command\Redis;


use Predis\Command\Redis\Utils\CommandUtility;

class DIGEST_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return DIGEST::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'DIGEST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key1'];
        $expected = ['key1'];

        $command = $this->getCommand();
        $command->setArguments($arguments);
        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requires PHP >= 8.1
     * @requiresRedisVersion >= 8.3.224
     */
    public function testReturnsDigestOfGivenValue(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertEquals(CommandUtility::xxh3Hash('bar'), $redis->digest('foo'));
    }
}
