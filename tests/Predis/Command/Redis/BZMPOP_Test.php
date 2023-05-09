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

namespace Predis\Command\Redis;

use Predis\Response\ServerException;
use UnexpectedValueException;

class BZMPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BZMPOP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BZMPOP';
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
     * @dataProvider responsesProvider
     */
    public function testParseResponse(array $actualResponse, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @dataProvider sortedSetsProvider
     * @param  int    $timeout
     * @param  array  $sortedSetDictionary
     * @param  string $key
     * @param  string $modifier
     * @param  int    $count
     * @param  array  $expectedResponse
     * @param  array  $expectedModifiedSortedSet
     * @return void
     * @requiresRedisVersion >= 7.0
     */
    public function testReturnsPoppedElementsFromGivenSortedSet(
        int $timeout,
        array $sortedSetDictionary,
        string $key,
        string $modifier,
        int $count,
        array $expectedResponse,
        array $expectedModifiedSortedSet
    ): void {
        $redis = $this->getClient();

        $redis->zadd($key, ...$sortedSetDictionary);
        $actualResponse = $redis->bzmpop($timeout, [$key], $modifier, $count);

        $this->assertEquals($expectedResponse, $actualResponse);
        $this->assertSame($expectedModifiedSortedSet, $redis->zrange($key, 0, -1));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  int    $timeout
     * @param  array  $keys
     * @param  string $modifier
     * @param  int    $count
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 7.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        int $timeout,
        array $keys,
        string $modifier,
        int $count,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->bzmpop($timeout, $keys, $modifier, $count);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('bzmpop_foo', 'bar');
        $redis->bzmpop(1, ['bzmpop_foo']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with one key' => [
                [10, ['key1'], 'min', 1],
                [10, 1, 'key1', 'MIN', 'COUNT', 1],
            ],
            'with multiple keys' => [
                [10, ['key1', 'key2', 'key3'], 'max', 1],
                [10, 3, 'key1', 'key2', 'key3', 'MAX', 'COUNT', 1],
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'null-element array' => [
                [null],
                [null],
            ],
            'two-element array' => [
                ['key', [['member1', 1, 'member2', 2, 'member3', 3]]],
                ['key' => ['member1' => 1, 'member2' => 2, 'member3' => 3]],
            ],
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'with MIN modifier' => [
                1,
                [1, 'member1', 2, 'member2', 3, 'member3'],
                'test-bzmpop',
                'min',
                1,
                ['test-bzmpop' => ['member1' => '1']],
                ['member2', 'member3'],
            ],
            'with MAX modifier' => [
                1,
                [1, 'member1', 2, 'member2', 3, 'member3'],
                'test-bzmpop',
                'max',
                1,
                ['test-bzmpop' => ['member3' => '3']],
                ['member1', 'member2'],
            ],
            'with non-default COUNT' => [
                1,
                [1, 'member1', 2, 'member2', 3, 'member3'],
                'test-bzmpop',
                'max',
                2,
                ['test-bzmpop' => ['member3' => '3', 'member2' => '2']],
                ['member1'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'wrong modifier' => [
                1,
                ['key1', 'key2'],
                'wrong modifier',
                1,
                'Wrong type of modifier given',
            ],
            'wrong count' => [
                1,
                ['key1', 'key2'],
                'min',
                0,
                'Wrong count argument value or position offset',
            ],
        ];
    }
}
