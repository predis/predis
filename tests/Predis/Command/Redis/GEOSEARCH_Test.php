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

class GEOSEARCH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return GEOSEARCH::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'GEOSEARCH';
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
    public function testParseResponse(array $actualResponse, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @dataProvider coordinatesProvider
     * @param  array         $firstCoordinates
     * @param  array         $secondCoordinates
     * @param  array         $thirdCoordinates
     * @param  string        $key
     * @param  FromInterface $from
     * @param  ByInterface   $by
     * @param  string|null   $sorting
     * @param  int           $count
     * @param  bool          $any
     * @param  bool          $withCoord
     * @param  bool          $withDist
     * @param  bool          $withHash
     * @param  array         $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsSearchedGeospatialCoordinates(
        array $firstCoordinates,
        array $secondCoordinates,
        array $thirdCoordinates,
        string $key,
        FromInterface $from,
        ByInterface $by,
        ?string $sorting,
        int $count,
        bool $any,
        bool $withCoord,
        bool $withDist,
        bool $withHash,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->geoadd(...$firstCoordinates);
        $redis->geoadd(...$secondCoordinates);
        $redis->geoadd(...$thirdCoordinates);

        $this->assertSame(
            $expectedResponse,
            $redis->geosearch($key, $from, $by, $sorting, $count, $any, $withCoord, $withDist, $withHash)
        );
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

        $redis->geosearch(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments - FROMLONLAT, BYRADIUS' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km')],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km'],
            ],
            'with default arguments - FROMMEMBER, BYBOX' => [
                ['key', new FromMember('member'), new ByBox(1, 1, 'km')],
                ['key', 'FROMMEMBER', 'member', 'BYBOX', 1, 1, 'km'],
            ],
            'with ASC sorting' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc'],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC'],
            ],
            'with DESC sorting' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'desc'],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'DESC'],
            ],
            'with COUNT argument - without ANY option' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20],
            ],
            'with COUNT argument - with ANY option' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20, 'ANY'],
            ],
            'with WITHCOORD argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHCOORD'],
            ],
            'with WITHDIST argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHDIST'],
            ],
            'with WITHHASH argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, false, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHHASH'],
            ],
            'with all arguments' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc', 20, true, true, true, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC', 'COUNT', 20, 'ANY', 'WITHCOORD', 'WITHDIST', 'WITHHASH'],
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'without WITH modifiers' => [
                ['member1', 'member2', 'member3'],
                ['member1', 'member2', 'member3'],
            ],
            'with WITHCOORD modifier' => [
                [['member1', [1.1, 2.2]], ['member2', [2.2, 3.3]], ['member3', [3.3, 4.4]]],
                [
                    'member1' => ['lng' => 1.1, 'lat' => 2.2],
                    'member2' => ['lng' => 2.2, 'lat' => 3.3],
                    'member3' => ['lng' => 3.3, 'lat' => 4.4]],
            ],
            'with WITHDIST modifier' => [
                [['member1', '111.111'], ['member2', '222.222'], ['member3', '333.333']],
                [
                    'member1' => ['dist' => 111.111],
                    'member2' => ['dist' => 222.222],
                    'member3' => ['dist' => 333.333],
                ],
            ],
            'with WITHHASH modifier' => [
                [['member1', 1111], ['member2', 2222], ['member3', 3333]],
                [
                    'member1' => ['hash' => 1111],
                    'member2' => ['hash' => 2222],
                    'member3' => ['hash' => 3333],
                ],
            ],
            'with all WITH modifiers' => [
                [
                    ['member1', '111.111', 1111, [1.1, 2.2]],
                    ['member2', '222.222', 2222, [2.2, 3.3]],
                    ['member3', '333.333', 3333, [3.3, 4.4]],
                ],
                [
                    'member1' => [
                        'dist' => 111.111,
                        'hash' => 1111,
                        'lng' => 1.1,
                        'lat' => 2.2,
                    ],
                    'member2' => [
                        'dist' => 222.222,
                        'hash' => 2222,
                        'lng' => 2.2,
                        'lat' => 3.3,
                    ],
                    'member3' => [
                        'dist' => 333.333,
                        'hash' => 3333,
                        'lng' => 3.3,
                        'lat' => 4.4,
                    ],
                ],
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
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                -1,
                false,
                false,
                false,
                false,
                ['member1', 'member2', 'member3'],
            ],
            'with default arguments - FROMLONLAT, BYRADIUS - closest members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 3),
                new ByRadius(200, 'km'),
                null,
                -1,
                false,
                false,
                false,
                false,
                ['member2', 'member1'],
            ],
            'with default arguments - FROMMEMBER, BYBOX - all members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromMember('member2'),
                new ByBox(999, 999, 'km'),
                null,
                -1,
                false,
                false,
                false,
                false,
                ['member1', 'member2', 'member3'],
            ],
            'with default arguments - FROMMEMBER, BYBOX - closest members' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromMember('member1'),
                new ByBox(300, 300, 'km'),
                null,
                -1,
                false,
                false,
                false,
                false,
                ['member1', 'member2'],
            ],
            'with ASC modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'asc',
                -1,
                false,
                false,
                false,
                false,
                ['member2', 'member1', 'member3'],
            ],
            'with DESC modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'desc',
                -1,
                false,
                false,
                false,
                false,
                ['member3', 'member1', 'member2'],
            ],
            'with COUNT modifier - without ANY option' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                1,
                false,
                false,
                false,
                false,
                ['member2'],
            ],
            'with COUNT modifier - with ANY option' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                2,
                true,
                false,
                false,
                false,
                ['member1', 'member2'],
            ],
            'with WITHCOORD modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                -1,
                false,
                true,
                false,
                false,
                [
                    'member1' => ['lng' => 1.1, 'lat' => 2.0],
                    'member2' => ['lng' => 2.1, 'lat' => 3.0],
                    'member3' => ['lng' => 3.1, 'lat' => 4.0],
                ],
            ],
            'with WITHDIST modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                -1,
                false,
                false,
                true,
                false,
                [
                    'member1' => ['dist' => 222.7297],
                    'member2' => ['dist' => 165.1798],
                    'member3' => ['dist' => 233.006],
                ],
            ],
            'with WITHHASH modifier' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                null,
                -1,
                false,
                false,
                false,
                true,
                [
                    'member1' => ['hash' => 3378086406303657],
                    'member2' => ['hash' => 3378965307136228],
                    'member3' => ['hash' => 3379626601756294],
                ],
            ],
            'with all arguments' => [
                ['key', 1.1, 2, 'member1'],
                ['key', 2.1, 3, 'member2'],
                ['key', 3.1, 4, 'member3'],
                'key',
                new FromLonLat(1, 4),
                new ByRadius(9999, 'km'),
                'asc',
                1,
                true,
                true,
                true,
                true,
                [
                    'member1' => [
                        'dist' => 222.7297,
                        'hash' => 3378086406303657,
                        'lng' => 1.1,
                        'lat' => 2.0,
                    ],
                ],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong FROM argument' => [
                ['key', false, new ByRadius(9999, 'km'), null, -1, false, false],
                InvalidArgumentException::class,
                'Invalid FROM argument value given',
            ],
            'with wrong BY argument' => [
                ['key', new FromLonLat(1, 4), false, null, -1, false, false],
                InvalidArgumentException::class,
                'Invalid BY argument value given',
            ],
            'with wrong sorting argument' => [
                ['key', new FromLonLat(1, 4), new ByRadius(9999, 'km'), 'wrong', -1, false, false],
                UnexpectedValueException::class,
                'Sorting argument accepts only: asc, desc values',
            ],
            'with wrong COUNT argument' => [
                ['key', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, false],
                UnexpectedValueException::class,
                'Wrong count argument value or position offset',
            ],
            'with wrong WITHCOORD argument' => [
                ['key', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, 'wrong'],
                UnexpectedValueException::class,
                'Wrong WITHCOORD argument type',
            ],
            'with wrong WITHDIST argument' => [
                ['key', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, false, 'wrong'],
                UnexpectedValueException::class,
                'Wrong WITHDIST argument type',
            ],
            'with wrong WITHHASH argument' => [
                ['key', new FromLonLat(1, 4), new ByRadius(9999, 'km'), null, 0, false, false, false, 'wrong'],
                UnexpectedValueException::class,
                'Wrong WITHHASH argument type',
            ],
        ];
    }
}
