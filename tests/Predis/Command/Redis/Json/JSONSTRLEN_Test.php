<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONSTRLEN_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONSTRLEN::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONSTRLEN';
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
     * @param array $expectedStringLength
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsLengthOfGivenJsonString(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedStringLength
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedStringLength, $redis->jsonstrlen($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                [6],
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$..key2',
                [6],
            ],
            'on both levels' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":"value2"}'],
                'key',
                '$..key2',
                [6, 6],
            ],
            'with non-json string' => [
                ['key', '$', '{"key1":{"key2":[1,2,3]}}'],
                'key',
                '$..key2',
                [null],
            ],
        ];
    }
}
