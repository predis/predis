<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

class JSONDEBUG_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONDEBUG::class;
    }

    /**
     * {@inheritDoc}
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
     * @group relay-incompatible
     * @dataProvider jsonProvider
     * @param  array  $jsonArguments
     * @param  string $key
     * @param  string $path
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testMemoryReturnsCorrectMemoryUsageAboutJson(
        array $jsonArguments,
        string $key,
        string $path
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertGreaterThan(0, $redis->jsondebug->memory($key, $path));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testMemoryReturnsCorrectMemoryUsageAboutJsonResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->jsonset('key', '$', '{"key1":"value1","key2":"value2"}');

        $this->assertGreaterThan(0, $redis->jsondebug->memory('key', '$'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testHelpReturnsInformationAboutContainerCommands(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->jsondebug->help();

        $this->assertStringContainsString('MEMORY', $actualResponse[0]);
        $this->assertStringContainsString('HELP', $actualResponse[1]);
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$',
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$..key2',
            ],
            'with same keys on both levels' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":"value2"}'],
                'key',
                '$..key2',
            ],
            'with wrong key' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key1',
                '$',
            ],
            'with wrong path' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$.key3',
            ],
        ];
    }
}
