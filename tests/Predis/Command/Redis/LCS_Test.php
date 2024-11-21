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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-string
 */
class LCS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return LCS::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LCS';
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
    public function testParseResponse($actualResponse, $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @dataProvider stringsProvider
     * @param  array $stringsArguments
     * @param  array $functionArguments
     * @param        $expectedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsLongestCommonSubsequenceFromGivenStrings(
        array $stringsArguments,
        array $functionArguments,
        $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->mset(...$stringsArguments);

        $this->assertSame($expectedResponse, $redis->lcs(...$functionArguments));
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments' => [
                ['key1', 'key2'],
                ['key1', 'key2'],
            ],
            'with LEN argument' => [
                ['key1', 'key2', true],
                ['key1', 'key2', 'LEN'],
            ],
            'with IDX argument' => [
                ['key1', 'key2', false, true],
                ['key1', 'key2', 'IDX'],
            ],
            'with MINMATCHLEN argument' => [
                ['key1', 'key2', false, false, 2],
                ['key1', 'key2', 'MINMATCHLEN', 2],
            ],
            'with WITHMATCHLEN argument' => [
                ['key1', 'key2', false, false, 0, true],
                ['key1', 'key2', 'WITHMATCHLEN'],
            ],
            'with all arguments' => [
                ['key1', 'key2', true, true, 2, true],
                ['key1', 'key2', 'LEN', 'IDX', 'MINMATCHLEN', 2, 'WITHMATCHLEN'],
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'non-array response' => [
                1,
                1,
            ],
            'array response' => [
                ['matches', [[[0, 1], [1, 2]]], 'len', 2],
                ['matches' => [[[0, 1], [1, 2]]], 'len' => 2],
            ],
        ];
    }

    public function stringsProvider(): array
    {
        return [
            'with required arguments' => [
                ['key1', 'value1', 'key2', '2value'],
                ['key1', 'key2'],
                'value',
            ],
            'only length' => [
                ['key1', 'value1', 'key2', '2value'],
                ['key1', 'key2', true],
                5,
            ],
            'with matching indexes - single match' => [
                ['key1', 'value1', 'key2', '2value'],
                ['key1', 'key2', false, true],
                [
                    'matches' => [
                        [
                            [0, 4],
                            [1, 5],
                        ],
                    ],
                    'len' => 5,
                ],
            ],
            'with matching indexes - multiple match' => [
                ['key1', 'value1test', 'key2', '2valuetest'],
                ['key1', 'key2', false, true],
                [
                    'matches' => [
                        [
                            [6, 9],
                            [6, 9],
                        ],
                        [
                            [0, 4],
                            [1, 5],
                        ],
                    ],
                    'len' => 9,
                ],
            ],
            'with matching indexes - MINMATCHLEN modifier' => [
                ['key1', 'value1test', 'key2', '2valuetest'],
                ['key1', 'key2', false, true, 5],
                [
                    'matches' => [
                        [
                            [0, 4],
                            [1, 5],
                        ],
                    ],
                    'len' => 9,
                ],
            ],
            'with matching indexes - WITHMATCHLEN modifier' => [
                ['key1', 'value1test', 'key2', '2valuetest'],
                ['key1', 'key2', false, true, 5, true],
                [
                    'matches' => [
                        [
                            [0, 4],
                            [1, 5],
                            5,
                        ],
                    ],
                    'len' => 9,
                ],
            ],
            'with wrong/empty keys arguments' => [
                ['key1', 'value1', 'key2', '2value'],
                ['key3', 'key4'],
                '',
            ],
        ];
    }
}
