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

namespace Predis\Command\Traits\With;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class WithDistTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class() extends RedisCommand {
            use WithDist;

            public static $withDistArgumentPositionOffset = 0;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param  int   $offset
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testReturnsCorrectArguments(int $offset, array $actualArguments, array $expectedArguments): void
    {
        $this->testClass::$withDistArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->testClass::$withDistArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong WITHDIST argument type');

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'WITHDIST false argument' => [
                0,
                [false, 'second argument', 'third argument'],
                [false, 'second argument', 'third argument'],
            ],
            'WITHDIST argument first and there is arguments after' => [
                0,
                [true, 'second argument', 'third argument'],
                ['WITHDIST', 'second argument', 'third argument'],
            ],
            'WITHDIST argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', true],
                ['first argument', 'second argument', 'WITHDIST'],
            ],
            'WITHDIST argument not the first and not the last' => [
                1,
                ['first argument', true, 'third argument'],
                ['first argument', 'WITHDIST', 'third argument'],
            ],
        ];
    }
}
