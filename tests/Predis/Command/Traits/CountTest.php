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

class CountTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Count;

            public static $countArgumentPositionOffset = 2;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param  int   $offset
     * @param  array $arguments
     * @param  bool  $any
     * @param  array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(int $offset, bool $any, array $arguments, array $expectedResponse): void
    {
        $this->testClass::$countArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments, $any);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    public function testThrowsErrorOnWrongCountValue(): void
    {
        $this->testClass::$countArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong count argument value or position offset');

        $this->testClass->setArguments([0]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with count argument' => [
                0,
                false,
                [2],
                ['COUNT', 2],
            ],
            'without count argument' => [
                2,
                false,
                ['argument1', 'argument2'],
                ['argument1', 'argument2'],
            ],
            'with count argument equal -1' => [
                0,
                false,
                [-1],
                [false],
            ],
            'with any modifier' => [
                0,
                true,
                [2],
                ['COUNT', 2, 'ANY'],
            ],
        ];
    }
}
