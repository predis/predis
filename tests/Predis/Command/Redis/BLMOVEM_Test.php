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

use Predis\Command\PrefixableCommand;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-list
 */
class BLMOVEM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BLMOVEM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BLMOVEM';
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
     * @dataProvider invalidArgumentsProvider
     */
    public function testSetArgumentsThrowsExceptionOnInvalidArguments(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->getCommand()->setArguments($arguments);
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'COUNT', 3, 'OBO'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:source', 'prefix:destination', 'LEFT', 'RIGHT', 0.1, 'COUNT', 3, 'OBO'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @dataProvider listsProvider
     * @param  array      $sourceList
     * @param  array      $destinationList
     * @param  array      $commandArguments
     * @param  array|null $expectedResponse
     * @param  array      $expectedSourceList
     * @param  array      $expectedDestinationList
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testMovesElementsBetweenLists(
        array $sourceList,
        array $destinationList,
        array $commandArguments,
        ?array $expectedResponse,
        array $expectedSourceList,
        array $expectedDestinationList
    ): void {
        $redis = $this->getClient();

        if ($sourceList) {
            $redis->rpush('source', $sourceList);
        }

        if ($destinationList) {
            $redis->rpush('destination', $destinationList);
        }

        $this->assertSame($expectedResponse, $redis->blmovem('source', 'destination', ...$commandArguments));
        $this->assertSame($expectedSourceList, $redis->lrange('source', 0, -1));
        $this->assertSame($expectedDestinationList, $redis->lrange('destination', 0, -1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testMovesElementsBetweenListsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->rpush('source', ['1', '2', '3', '4', '5']);
        $redis->rpush('destination', ['6', '7', '8', '9', '10']);

        $this->assertSame(
            ['3', '2', '1'],
            $redis->blmovem('source', 'destination', 'LEFT', 'LEFT', 0.1, 'COUNT', 3, 'OBO')
        );
        $this->assertSame(['4', '5'], $redis->lrange('source', 0, -1));
        $this->assertSame(
            ['3', '2', '1', '6', '7', '8', '9', '10'],
            $redis->lrange('destination', 0, -1)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');

        $redis->set('source', 'foo');
        $redis->blmovem('source', 'destination', 'LEFT', 'RIGHT', 0.1);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1],
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1],
            ],
            'with COUNT quantifier' => [
                ['source', 'destination', 'LEFT', 'LEFT', 0, 'COUNT', 3, 'OBO'],
                ['source', 'destination', 'LEFT', 'LEFT', 0, 'COUNT', 3, 'OBO'],
            ],
            'with EXACTLY quantifier' => [
                ['source', 'destination', 'RIGHT', 'RIGHT', 1.5, 'EXACTLY', 2, 'BULK'],
                ['source', 'destination', 'RIGHT', 'RIGHT', 1.5, 'EXACTLY', 2, 'BULK'],
            ],
            'with lowercase quantifier and ordering' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'count', 3, 'bulk'],
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'COUNT', 3, 'BULK'],
            ],
        ];
    }

    public function invalidArgumentsProvider(): array
    {
        return [
            'with invalid quantifier' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'WRONG', 3, 'OBO'],
                'Quantifier argument accepts only: COUNT, EXACTLY values',
            ],
            'with missing count' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'COUNT'],
                'COUNT quantifier requires a count argument',
            ],
            'with missing ordering' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'EXACTLY', 2],
                'EXACTLY quantifier requires an ordering argument',
            ],
            'with invalid ordering' => [
                ['source', 'destination', 'LEFT', 'RIGHT', 0.1, 'COUNT', 3, 'WRONG'],
                'Ordering argument accepts only: OBO, BULK values',
            ],
        ];
    }

    public function listsProvider(): array
    {
        return [
            'moves single element without quantifier' => [
                ['1', '2', '3', '4', '5'],
                [],
                ['LEFT', 'RIGHT', 0.1],
                ['1'],
                ['2', '3', '4', '5'],
                ['1'],
            ],
            'with COUNT and OBO - reversed block order' => [
                ['1', '2', '3', '4', '5'],
                ['6', '7', '8', '9', '10'],
                ['LEFT', 'LEFT', 0.1, 'COUNT', 3, 'OBO'],
                ['3', '2', '1'],
                ['4', '5'],
                ['3', '2', '1', '6', '7', '8', '9', '10'],
            ],
            'with COUNT and BULK - preserved relative order' => [
                ['1', '2', '3', '4', '5'],
                ['6', '7', '8', '9', '10'],
                ['LEFT', 'LEFT', 0.1, 'COUNT', 3, 'BULK'],
                ['1', '2', '3'],
                ['4', '5'],
                ['1', '2', '3', '6', '7', '8', '9', '10'],
            ],
            'with mixed directions - RIGHT to RIGHT' => [
                ['1', '2', '3', '4', '5'],
                ['6', '7', '8', '9', '10'],
                ['RIGHT', 'RIGHT', 0.1, 'COUNT', 3, 'BULK'],
                ['3', '4', '5'],
                ['1', '2'],
                ['6', '7', '8', '9', '10', '3', '4', '5'],
            ],
            'with COUNT greater than source length - moves fewer immediately' => [
                ['1', '2'],
                [],
                ['LEFT', 'RIGHT', 0.1, 'COUNT', 5, 'BULK'],
                ['1', '2'],
                [],
                ['1', '2'],
            ],
            'with EXACTLY and enough elements' => [
                ['john', 'doe'],
                [],
                ['LEFT', 'RIGHT', 0.1, 'EXACTLY', 2, 'BULK'],
                ['john', 'doe'],
                [],
                ['john', 'doe'],
            ],
            'with EXACTLY and too few elements - times out moving nothing' => [
                ['john'],
                [],
                ['LEFT', 'RIGHT', 0.1, 'EXACTLY', 2, 'BULK'],
                null,
                ['john'],
                [],
            ],
            'with empty source - times out moving nothing' => [
                [],
                ['6', '7'],
                ['LEFT', 'RIGHT', 0.1, 'COUNT', 2, 'BULK'],
                null,
                [],
                ['6', '7'],
            ],
        ];
    }
}
