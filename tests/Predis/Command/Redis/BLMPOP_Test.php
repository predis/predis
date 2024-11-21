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
 * @group realm-list
 */
class BLMPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return BLMPOP::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BLMPOP';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['key', ['elem1', 'elem2']];
        $expected = ['key' => ['elem1', 'elem2']];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @dataProvider listProvider
     * @param  int    $timeout
     * @param  array  $listArguments
     * @param  string $key
     * @param  string $modifier
     * @param  int    $count
     * @param  array  $expectedResponse
     * @param  array  $expectedModifiedList
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testPopElementsFromGivenList(
        int $timeout,
        array $listArguments,
        string $key,
        string $modifier,
        int $count,
        array $expectedResponse,
        array $expectedModifiedList
    ): void {
        $redis = $this->getClient();

        $redis->lpush(...$listArguments);
        $actualResponse = $redis->blmpop($timeout, ['key1', $key], $modifier, $count);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedModifiedList, $redis->lrange($key, 0, -1));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [1, ['key']],
                [1, 1, 'key', 'LEFT'],
            ],
            'with LEFT/RIGHT argument' => [
                [1, ['key'], 'right'],
                [1, 1, 'key', 'RIGHT'],
            ],
            'with COUNT argument' => [
                [1, ['key'], 'left', 2],
                [1, 1, 'key', 'LEFT', 'COUNT', 2],
            ],
            'with all arguments' => [
                [1, ['key1', 'key2'], 'right', 2],
                [1, 2, 'key1', 'key2', 'RIGHT', 'COUNT', 2],
            ],
        ];
    }

    public function listProvider(): array
    {
        return [
            'pops single element - left' => [
                1,
                ['key', 'elem1', 'elem2', 'elem3'],
                'key',
                'left',
                1,
                ['key' => ['elem3']],
                ['elem2', 'elem1'],
            ],
            'pops single element - right' => [
                1,
                ['key', 'elem1', 'elem2', 'elem3'],
                'key',
                'right',
                1,
                ['key' => ['elem1']],
                ['elem3', 'elem2'],
            ],
            'pops multiple elements' => [
                1,
                ['key', 'elem1', 'elem2', 'elem3'],
                'key',
                'right',
                2,
                ['key' => ['elem1', 'elem2']],
                ['elem3'],
            ],
        ];
    }
}
