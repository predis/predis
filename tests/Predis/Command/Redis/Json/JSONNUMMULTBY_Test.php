<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONNUMMULTBY_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONNUMMULTBY::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONNUMMULTBY';
    }


    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 5];
        $expected = ['key', '$..', 5];

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
     * @param int $value
     * @param string $expectedIncrementedResponse
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testMultiplyJsonNumericOnGivenValue(
        array $jsonArguments,
        string $key,
        string $path,
        int $value,
        string $expectedIncrementedResponse
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedIncrementedResponse, $redis->jsonnummultby($key, $path, $value));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":1}'],
                'key',
                '$.key2',
                5,
                "[5]",
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":5}}'],
                'key',
                '$..key2',
                3,
                "[15]",
            ],
            'on both levels' => [
                ['key', '$', '{"key1":{"key2":5},"key2":4}'],
                'key',
                '$..key2',
                2,
                "[8,10]",
            ],
            'with non-numeric' => [
                ['key', '$', '{"key1":{"key2":[1,2,3]}}'],
                'key',
                '$..key2',
                2,
                "[null]",
            ],
        ];
    }
}
