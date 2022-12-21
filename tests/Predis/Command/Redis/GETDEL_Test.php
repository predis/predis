<?php

namespace Predis\Command\Redis;

class GETDEL_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return GETDEL::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'GETDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsValueForGivenKeyAndDeleteIt(): void
    {
        $redis = $this->getClient();
        $expectedKey = 'key';
        $expectedValue = 'value';

        $redis->set($expectedKey, $expectedValue);

        $actualResponse = $redis->getdel($expectedKey);

        $this->assertSame($expectedValue, $actualResponse);
        $this->assertNull($redis->get($expectedKey));
    }
}
