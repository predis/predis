<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONDEBUG_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONDEBUG::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONDEBUG';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['MEMORY', 'key', '$'];
        $expected = ['MEMORY', 'key', '$'];

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
     * @param array $expectedMemoryUsage
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testMemoryReturnsCorrectMemoryUsageAboutJson(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedMemoryUsage
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedMemoryUsage, $redis->jsondebug->memory($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$',
                [44]
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$..key2',
                [14]
            ],
            'with same keys on both levels' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":"value2"}'],
                'key',
                '$..key2',
                [14, 14]
            ],
            'with wrong key' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key1',
                '$',
                []
            ],
            'with wrong path' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$.key3',
                []
            ],
        ];
    }
}
