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

namespace Predis\Command\Redis\TopK;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TOPKRESERVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TOPKRESERVE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOPKRESERVE';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
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
     * @group relay-incompatible
     * @dataProvider structureProvider
     * @param  array  $topKArguments
     * @param  string $key
     * @param  array  $expectedInfoResponse
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testReserveInitializeTopKStructureWithGivenConfiguration(
        array $topKArguments,
        string $key,
        array $expectedInfoResponse
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->topkreserve(...$topKArguments);
        $actualInfoResponse = $redis->topkinfo($key);

        $this->assertEquals('OK', $actualResponse);
        $this->assertSameWithPrecision($expectedInfoResponse, $actualInfoResponse, 1);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReserveInitializeTopKStructureWithGivenConfigurationResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->topkreserve('key', 50);
        $actualInfoResponse = $redis->topkinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSameWithPrecision(
            ['k' => 50, 'width' => 8, 'depth' => 7, 'decay' => '0.90000000000000002'],
            $actualInfoResponse,
            1
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnAlreadyExistingKey(): void
    {
        $redis = $this->getClient();

        $redis->topkreserve('key', 50);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('TopK: key already exists');

        $redis->topkreserve('key', 50);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 10],
                ['key', 10],
            ],
            'with non-default width' => [
                ['key', 10, 5],
                ['key', 10, 5, 7, 0.9],
            ],
            'with non-default depth' => [
                ['key', 10, 8, 9],
                ['key', 10, 8, 9, 0.9],
            ],
            'with non-default decay' => [
                ['key', 10, 8, 7, 0.8],
                ['key', 10, 8, 7, 0.8],
            ],
        ];
    }

    public function structureProvider(): array
    {
        return [
            'with default configuration' => [
                ['key', 50],
                'key',
                ['k' => 50, 'width' => 8, 'depth' => 7, 'decay' => '0.90000000000000002'],
            ],
            'with non-default width' => [
                ['key', 50, 9],
                'key',
                ['k' => 50, 'width' => 9, 'depth' => 7, 'decay' => '0.90000000000000002'],
            ],
            'with non-default depth' => [
                ['key', 50, 8, 9],
                'key',
                ['k' => 50, 'width' => 8, 'depth' => 9, 'decay' => '0.90000000000000002'],
            ],
            'with non-default decay' => [
                ['key', 50, 8, 7, 0.8],
                'key',
                ['k' => 50, 'width' => 8, 'depth' => 7, 'decay' => '0.80000000000000004'],
            ],
            'with all arguments non-default' => [
                ['key', 50, 9, 6, 0.8],
                'key',
                ['k' => 50, 'width' => 9, 'depth' => 6, 'decay' => '0.80000000000000004'],
            ],
        ];
    }
}
