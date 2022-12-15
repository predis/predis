<?php

namespace Predis\Command\Redis;

use Predis\Command\Argument\Geospatial\ByBox;
use Predis\Command\Argument\Geospatial\ByInterface;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Argument\Geospatial\FromInterface;
use Predis\Command\Argument\Geospatial\FromLonLat;
use Predis\Command\Argument\Geospatial\FromMember;

class GEOSEARCH_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return GEOSEARCH::class;
    }

    /**
     * @inheritDoc
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
     * @group connected
     * @dataProvider coordinatesProvider
     * @param array $firstCoordinates
     * @param array $secondCoordinates
     * @param array $thirdCoordinates
     * @param string $key
     * @param FromInterface $from
     * @param ByInterface $by
     * @param string|null $sorting
     * @param int $count
     * @param bool $any
     * @param bool $withCoord
     * @param bool $withDist
     * @param bool $withHash
     * @param array $expectedResponse
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

    public function argumentsProvider(): array
    {
        return [
            'with default arguments - FROMLONLAT, BYRADIUS' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km')],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km']
            ],
            'with default arguments - FROMMEMBER, BYBOX' => [
                ['key', new FromMember('member'), new ByBox(1,1, 'km')],
                ['key', 'FROMMEMBER', 'member', 'BYBOX', 1, 1, 'km']
            ],
            'with ASC sorting' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc'],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC']
            ],
            'with DESC sorting' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'desc'],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'DESC']
            ],
            'with COUNT argument - without ANY option' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20]
            ],
            'with COUNT argument - with ANY option' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, 20, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'COUNT', 20, 'ANY']
            ],
            'with WITHCOORD argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHCOORD']
            ],
            'with WITHDIST argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHDIST']
            ],
            'with WITHHASH argument' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), null, -1, false, false, false, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'WITHHASH']
            ],
            'with all arguments' => [
                ['key', new FromLonLat(1.1, 2.2), new ByRadius(1, 'km'), 'asc', 20, true, true, true, true],
                ['key', 'FROMLONLAT', 1.1, 2.2, 'BYRADIUS', 1, 'km', 'ASC', 'COUNT', 20, 'ANY', 'WITHCOORD', 'WITHDIST', 'WITHHASH']
            ]
        ];
    }

    public function coordinatesProvider(): array
    {
        return [
            'with default arguments - FROMLONLAT, BYRADIUS - all member' => [
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
                ['member1', 'member2', 'member3']
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
                ['member2', 'member1']
            ]
        ];
    }
}
