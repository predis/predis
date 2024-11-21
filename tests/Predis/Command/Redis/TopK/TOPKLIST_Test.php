<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
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
class TOPKLIST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TOPKLIST::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOPKLIST';
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
     * @dataProvider responsesProvider
     */
    public function testParseResponse(array $arguments, array $actualResponse, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expectedResponse, $command->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @dataProvider structureProvider
     * @param  array $reserveArguments
     * @param  array $addArguments
     * @param  array $listArguments
     * @param  array $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testReturnsListOfItemsWithinTopKStructure(
        array $reserveArguments,
        array $addArguments,
        array $listArguments,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->topkreserve(...$reserveArguments);
        $redis->topkadd(...$addArguments);

        $actualResponse = $redis->topklist(...$listArguments);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('TopK: key does not exist');

        $redis->topklist('key', true);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default argument' => [
                ['key'],
                ['key'],
            ],
            'with WITHCOUNT modifier equals true' => [
                ['key', true],
                ['key', 'WITHCOUNT'],
            ],
            'with WITHCOUNT modifier equals false' => [
                ['key', false],
                ['key'],
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'without WITHCOUNT modifier' => [
                ['foo'],
                ['foo', 'bar'],
                ['foo', 'bar'],
            ],
            'with WITHCOUNT modifier' => [
                ['foo', true],
                ['foo', 12, 'bar', 13],
                ['foo' => 12, 'bar' => 13],
            ],
        ];
    }

    public function structureProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 50],
                ['key', 1, 2, 2, 3, 3, 3],
                ['key', false],
                ['3', '2', '1'],
            ],
            'with WITHCOUNT modifier' => [
                ['key', 50],
                ['key', 1, 2, 2, 3, 3, 3],
                ['key', true],
                ['3' => 3, '2' => 2, '1' => 1],
            ],
        ];
    }
}
