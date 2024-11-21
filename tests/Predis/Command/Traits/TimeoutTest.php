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

class TimeoutTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Timeout;

            public static $timeoutArgumentPositionOffset = 0;

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
        $this->testClass::$timeoutArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong timeout argument value or position offset');

        $this->testClass->setArguments([0]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with TIMEOUT argument' => [
                0,
                [10],
                ['TIMEOUT', 10],
            ],
            'with wrong offset given' => [
                1,
                [],
                [],
            ],
            'with default value' => [
                0,
                [-1],
                [false],
            ],
        ];
    }
}
