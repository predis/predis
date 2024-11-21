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

namespace Predis\Command\Traits\To;

use Predis\Command\Argument\Server\To;
use Predis\Command\Command as RedisCommand;
use PredisTestCase;

class ServerToTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use ServerTo;

            public static $toArgumentPositionOffset = 0;

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
        $this->testClass::$toArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with TO argument - no FORCE' => [
                0,
                [new To('host', 9999)],
                ['TO', 'host', 9999],
            ],
            'with TO argument - with FORCE' => [
                0,
                [new To('host', 9999, true)],
                ['TO', 'host', 9999, 'FORCE'],
            ],
            'with wrong offset given' => [
                1,
                [],
                [],
            ],
            'with default value' => [
                0,
                [null],
                [false],
            ],
        ];
    }
}
