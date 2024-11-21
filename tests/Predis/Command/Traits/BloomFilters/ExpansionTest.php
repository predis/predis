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

namespace Predis\Command\Traits\BloomFilters;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class ExpansionTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use Expansion;

            public static $expansionArgumentPositionOffset = 0;

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
        $this->testClass::$expansionArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong expansion argument value or position offset');

        $this->testClass->setArguments([-2]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [2, ['argument1'], ['argument1']],
            'with value equals -1' => [0, [-1], [false]],
            'with correct value' => [0, [1], ['EXPANSION', 1]],
        ];
    }
}
