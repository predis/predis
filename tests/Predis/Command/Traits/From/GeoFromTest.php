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

namespace Predis\Command\Traits\From;

use InvalidArgumentException;
use Predis\Command\Argument\Geospatial\FromLonLat;
use Predis\Command\Argument\Geospatial\FromMember;
use Predis\Command\Command as RedisCommand;
use PredisTestCase;

class GeoFromTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use GeoFrom;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testReturnsCorrectArguments(array $actualArguments, array $expectedArguments): void
    {
        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FROM argument value given');

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'FROMLONLAT argument' => [
                ['first argument', new FromLonLat(1.1, 2.2), 'third argument'],
                ['first argument', 'FROMLONLAT', 1.1, 2.2, 'third argument'],
            ],
            'FROMMEMBER argument' => [
                ['first argument', new FromMember('member1'), 'third argument'],
                ['first argument', 'FROMMEMBER', 'member1', 'third argument'],
            ],
        ];
    }
}
