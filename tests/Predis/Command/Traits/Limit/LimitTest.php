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

namespace Predis\Command\Traits\Limit;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class LimitTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Limit;

            public static $limitArgumentPositionOffset = 0;

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
        $this->testClass::$limitArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->testClass::$limitArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong limit argument type');

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'limit false argument first and there is arguments after' => [
                0,
                [false, 'second argument', 'third argument'],
                [],
            ],
            'limit false argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', false],
                ['first argument', 'second argument'],
            ],
            'limit false argument not the first and not the last' => [
                1,
                ['first argument', false, 'third argument'],
                ['first argument'],
            ],
            'limit argument first and there is arguments after' => [
                0,
                [true, 'second argument', 'third argument'],
                ['LIMIT', 'second argument', 'third argument'],
            ],
            'limit argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', true],
                ['first argument', 'second argument', 'LIMIT'],
            ],
            'limit argument not the first and not the last' => [
                1,
                ['first argument', true, 'third argument'],
                ['first argument', 'LIMIT', 'third argument'],
            ],
            'limit argument is integer' => [
                0,
                [1],
                ['LIMIT', 1],
            ],
            'limit argument with wrong offset' => [
                2,
                [1],
                [1],
            ],
        ];
    }
}
