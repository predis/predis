<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONOBJLEN_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONOBJLEN::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONOBJLEN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..'];
        $expected = ['key', '$..'];

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
     * @dataProvider jsonProvider
     * @param array $jsonArguments
     * @param string $key
     * @param string $path
     * @param array $expectedObjectLength
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsLengthOfGivenJsonObject(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedObjectLength
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedObjectLength, $redis->jsonobjlen($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$',
                [2]
            ],
            'on nested level' => [
                ['key', '$', '{"key1":"value1","key2":{"key1":"value1","key2":"value2"}}'],
                'key',
                '$.key2',
                [2]
            ],
            'with same key on both levels' => [
                ['key', '$', '{"key1":{"key3":"value3","key4":"value4"},"key2":{"key1":{"key2":"value2"}}}'],
                'key',
                '$..key1',
                [2, 1]
            ],
            'with one of the keys not a JSON object' => [
                ['key', '$', '{"key1":"value1","key2":{"key1":{"key2":"value2"}}}'],
                'key',
                '$..key1',
                [null, 1]
            ],
        ];
    }
}
