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

use InvalidArgumentException;
use Predis\Command\Argument\Geospatial\ByBox;
use Predis\Command\Argument\Geospatial\ByInterface;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Argument\Geospatial\FromInterface;
use Predis\Command\Argument\Geospatial\FromLonLat;
use Predis\Command\Argument\Geospatial\FromMember;
use UnexpectedValueException;

class GEOSEARCHSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return GEOSEARCHSTORE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'GEOSEARCHSTORE';
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
     * @dataProvider coordinatesProvider
     * @param  array         $firstCoordinates
     * @param  array         $secondCoordinates
     * @param  array         $thirdCoordinates
     * @param  string        $destination
     * @param  string        $source
     * @param  FromInterface $from
     * @param  ByInterface   $by
     * @param  string|null   $sorting
     * @param  int           $count
     * @param  bool          $any
     * @param  int           $expectedResultingElements
     * @param  array         $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testStoresCorrectGivenGeospatialCoordinates(
        array $firstCoordinates,
        array $secondCoordinates,
        array $thirdCoordinates,
        string $destination,
        string $source,
        FromInterface $from,
        ByInterface $by,
        ?string $sorting,
        int $count,
        bool $any,
        int $expectedResultingElements,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->geoadd(...$firstCoordinates);
        $redis->geoadd(...$secondCoordinates);
        $redis->geoadd(...$thirdCoordinates);

        $actualResultingElements = $redis->geosearchstore(
            $destination,
            $source,
            $from,
            $by,
            $sorting,
            $count,
            $any
        );

        $this->assertSame($expectedResultingElements, $actualResultingElements);
        $this->assertSame($expectedResponse, $redis->geosearch($destination, $from, $by, $sorting, $count, $any));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testStoresInSortedSetWithStoreDistArgumentProvided(): void
    {
        $redis = $this->getClient();

        $redis->geoadd('key', 1.1, 2, 'member1');
        $redis->geoadd('key', 2.1, 3, 'member2');
        $redis->geoadd('key', 3.1, 4, 'member3');

        $actualResultingElements = $redis->geosearchstore(
            'destination',
            'key',
            new FromLonLat(1, 4),
            new ByRadius(9999, 'km'),
            null,
            2,
            false,
            true
        );

        $this->assertSame(2, $actualResultingElements);
        $this->assertSame(['member2', 'member1'], $redis->zrange('destination', 0, -1));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedException
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnUnexpectedValueProvided(
        array $arguments,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->geosearchstore(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments - FROMLONLAT, BYRADIUS' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km')],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km'],
            ],
            'with default arguments - FROMMEMBER, BYBOX' => [
                ['destination', 'source', new FromMember('member'), new ByBox(1, 1, 'km')],
                ['destination', 'source', 'FROMMEMBER', 'member', 'BYBOX', 1, 1, 'km'],
            ],
            'with ASC sorting' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc'],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC'],
            ],
            'with DESC sorting' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'desc'],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'DESC'],
            ],
            'with COUNT argument - without ANY option' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20],
            ],
            'with COUNT argument - with ANY option' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20, true],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20, 'ANY'],
            ],
            'with STOREDIST argument' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, true],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'STOREDIST'],
            ],
            'with all arguments' => [
                ['destination', 'source', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc', 20, true, true],
                ['destination', 'source', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC', 'COUNT', 20, 'ANY', 'STOREDIST'],
            ],
        ];
    }

    public function coordinatesProvider(): array
    {
        return [
            'with default arguments - FROMLONLAT, BYRADIUS - all members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                -1,
                false,
                3,
                ['member1', 'member2', 'member3'],
            ],
            'with default arguments - FROMLONLAT, BYRADIUS - closest members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 3),
                new ByRadius(200, 'km'),
                null,
                -1,
                false,
                2,
                ['member2', 'member1'],
            ],
            'with default arguments - FROMMEMBER, BYBOX - all members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromMember('member2'),
                new ByBox(999, 999, 'km'),
                null,
                -1,
                false,
                3,
                ['member1', 'member2', 'member3'],
            ],
            'with default arguments - FROMMEMBER, BYBOX - closest members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromMember('member1'),
                new ByBox(300, 300, 'km'),
                null,
                -1,
                false,
                2,
                ['member1', 'member2'],
            ],
            'with ASC modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'asc',
                -1,
                false,
                3,
                ['member2', 'member1', 'member3'],
            ],
            'with DESC modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'desc',
                -1,
                false,
                3,
                ['member3', 'member1', 'member2'],
            ],
            'with COUNT modifier - without ANY option' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                1,
                false,
                1,
                ['member2'],
            ],
            'with COUNT modifier - with ANY option' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                2,
                true,
                2,
                ['member1', 'member2'],
            ],
            'with all arguments' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'destination',
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'asc',
                2,
                true,
                2,
                ['member2', 'member1'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong FROM argument' => [
                ['destination', 'source', false, new ByRadius(9999, 'km'), null, -1, false, false],
                InvalidArgumentException::class,
                'Invalid FROM argument value given',
            ],
            'with wrong BY argument' => [
                ['destination', 'source', new FromLonLat(1, 4), false, null, -1, false, false],
                InvalidArgumentException::class,
                'Invalid BY argument value given',
            ],
            'with wrong sorting argument' => [
                ['destination', 'source', new FromLonLat(1, 4), new ByRadius(9999, 'km'), 'wrong', -1, false, false],
                UnexpectedValueException::class,
                'Sorting argument accepts only: asc, desc values',
            ],
            'with wrong COUNT argument' => [
                ['destination', 'source', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, false],
                UnexpectedValueException::class,
                'Wrong count argument value or position offset',
            ],
            'with wrong STOREDIST argument' => [
                ['destination', 'source', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, 'wrong'],
                UnexpectedValueException::class,
                'Wrong STOREDIST argument type',
            ],
        ];
    }
}
