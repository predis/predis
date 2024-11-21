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

namespace Predis\Command\Traits\By;

use InvalidArgumentException;
use Predis\Command\Argument\Geospatial\ByBox;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Command as RedisCommand;
use PredisTestCase;

class GeoByTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use GeoBy;

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
        $this->expectExceptionMessage('Invalid BY argument value given');

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'BYRADIUS argument' => [
                ['first argument', new ByRadius(1, 'km'), 'third argument'],
                ['first argument', 'BYRADIUS', 1, 'km', 'third argument'],
            ],
            'BYBOX argument' => [
                ['first argument', new ByBox(1, 1, 'km'), 'third argument'],
                ['first argument', 'BYBOX', 1, 1, 'km', 'third argument'],
            ],
        ];
    }
}
