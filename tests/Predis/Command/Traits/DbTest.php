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

class DbTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use DB;

            public static $dbArgumentPositionOffset = 0;

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
     * @param  array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(int $offset, array $arguments, array $expectedResponse): void
    {
        $this->testClass::$dbArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSame($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsErrorOnUnexpectedValueGiven(): void
    {
        $this->testClass::$dbArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('DB argument should be a valid numeric value');

        $this->testClass->setArguments(['wrong']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with positive integer db argument' => [
                0,
                [1],
                ['DB', 1],
            ],
            'with wrong offset' => [
                1,
                [1],
                [1],
            ],
            'with negative integer db argument' => [
                1,
                ['argument1', -1],
                ['argument1'],
            ],
        ];
    }
}
