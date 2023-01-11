<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONARRLEN_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRLEN::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRLEN';
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
     * @param array $expectedLength
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsCorrectJsonArrayLength(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedLength
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedLength, $redis->jsonarrlen($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                [2]
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]}}'],
                'key',
                '$..key2',
                [2]
            ],
            'with same keys on both levels' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value1","value2","value3"]}'],
                'key',
                '$..key2',
                [3,2]
            ],
            'with non-array path' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                [null]
            ]
        ];
    }
}
