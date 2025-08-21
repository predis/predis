<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Traits\BloomFilters;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class ErrorTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Error;

            public static $errorArgumentPositionOffset = 0;

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
        $this->testClass::$errorArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsErrorOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong error argument value or position offset');

        $this->testClass->setArguments([-5]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [
                1,
                [],
                [],
            ],
            'with default argument' => [
                0,
                [-1],
                [false],
            ],
            'with non-default argument' => [
                0,
                [0.01],
                ['ERROR', 0.01],
            ],
        ];
    }
}
