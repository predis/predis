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

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class LeftRightTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use LeftRight;

            public static $leftRightArgumentPositionOffset = 0;

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
        $this->testClass::$leftRightArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider unexpectedValuesProvider
     * @param  array $actualArguments
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(array $actualArguments): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Left/Right argument accepts only: left, right values');

        $this->testClass->setArguments($actualArguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [2, ['argument1'], ['argument1', 'LEFT']],
            'left/right argument first and there is arguments after' => [
                0,
                ['left', 'second argument', 'third argument'],
                ['LEFT', 'second argument', 'third argument'],
            ],
            'left/right argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', 'right'],
                ['first argument', 'second argument', 'RIGHT'],
            ],
            'left/right argument not the first and not the last' => [
                1,
                ['first argument', 'left', 'third argument'],
                ['first argument', 'LEFT', 'third argument'],
            ],
            'aggregate argument the only argument' => [
                0,
                ['right'],
                ['RIGHT'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with non-string argument' => [[1]],
            'with non enum value' => [['wrong']],
        ];
    }
}
