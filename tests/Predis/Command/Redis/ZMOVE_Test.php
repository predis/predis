<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-zset
 */
class ZMOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZMOVE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZMOVE';
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
        $command = $this->getCommand();

        $this->assertSame(['a' => '1', 'b' => '2'], $command->parseResponse([['a', '1'], ['b', '2']]));
        $this->assertNull($command->parseResponse(null));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['source', 'destination', 'min', 2]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:source', 'prefix:destination', 'MIN', 'COUNT', 2], $command->getArguments());
    }

    /**
     * @group connected
     * @dataProvider movesProvider
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSets(
        array $commandArguments,
        ?array $expectedResponse,
        array $expectedSource,
        array $expectedDestination
    ): void {
        $redis = $this->getClient();

        $redis->zadd('source', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('destination', 10, 'z');

        $this->assertSame($expectedResponse, $redis->zmove('source', 'destination', ...$commandArguments));
        $this->assertSame($expectedSource, $redis->zrange('source', 0, -1, ['withscores' => true]));
        $this->assertSame($expectedDestination, $redis->zrange('destination', 0, -1, ['withscores' => true]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSetsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('source', 1, 'a', 2, 'b', 3, 'c');

        $this->assertSame(['c' => 3.0, 'b' => 2.0], $redis->zmove('source', 'destination', 'max', 2));
        $this->assertNull($redis->zmove('source', 'destination', [10, 20]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis->set('source', 'foo');
        $redis->zmove('source', 'destination', 'min');
    }

    public function argumentsProvider(): array
    {
        return [
            'with MIN' => [
                ['source', 'destination', 'min'],
                ['source', 'destination', 'MIN'],
            ],
            'with MAX and COUNT' => [
                ['source', 'destination', 'MAX', 3],
                ['source', 'destination', 'MAX', 'COUNT', 3],
            ],
            'with BYSCORE' => [
                ['source', 'destination', [1, '(5']],
                ['source', 'destination', 'BYSCORE', 1, '(5'],
            ],
            'with BYSCORE and COUNT' => [
                ['source', 'destination', ['-inf', '+inf'], 10],
                ['source', 'destination', 'BYSCORE', '-inf', '+inf', 'COUNT', 10],
            ],
        ];
    }

    public function movesProvider(): array
    {
        return [
            'MIN moves single lowest member' => [
                ['min'],
                ['a' => '1'],
                ['b' => '2', 'c' => '3'],
                ['a' => '1', 'z' => '10'],
            ],
            'MAX with COUNT moves highest members' => [
                ['max', 2],
                ['c' => '3', 'b' => '2'],
                ['a' => '1'],
                ['b' => '2', 'c' => '3', 'z' => '10'],
            ],
            'BYSCORE moves score range' => [
                [[2, 3]],
                ['b' => '2', 'c' => '3'],
                ['a' => '1'],
                ['b' => '2', 'c' => '3', 'z' => '10'],
            ],
            'BYSCORE with COUNT limits batch' => [
                [['-inf', '+inf'], 1],
                ['a' => '1'],
                ['b' => '2', 'c' => '3'],
                ['a' => '1', 'z' => '10'],
            ],
            'BYSCORE with no matches moves nothing' => [
                [[100, 200]],
                null,
                ['a' => '1', 'b' => '2', 'c' => '3'],
                ['z' => '10'],
            ],
        ];
    }
}
