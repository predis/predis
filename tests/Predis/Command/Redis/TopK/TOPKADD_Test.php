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
class TOPKADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TOPKADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOPKADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item1', 'item2'];
        $expectedArguments = ['key', 'item1', 'item2'];

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
     * @dataProvider structuresProvider
     * @param  array $reserveArguments
     * @param  array $addArguments
     * @param  array $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testAddItemsIntoGivenTopKStructure(
        array $reserveArguments,
        array $addArguments,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->topkreserve(...$reserveArguments);

        $actualResponse = $redis->topkadd(...$addArguments);
        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testAddItemsIntoGivenTopKStructureResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->topkreserve('key', 2);

        $actualResponse = $redis->topkadd('key', 0, 1, 2);
        $this->assertEquals([null, null, '0'], $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('TopK: key does not exist');

        $redis->topkadd('key', 0, 1);
    }

    public function structuresProvider(): array
    {
        return [
            'without dropped items' => [
                ['key', 4],
                ['key', 0, 1, 2],
                [null, null, null],
            ],
            'with dropped items' => [
                ['key', 2],
                ['key', 0, 1, 2],
                [null, null, '0'],
            ],
        ];
    }
}
