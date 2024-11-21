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

class ReplaceTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Replace;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(array $arguments, array $expectedResponse): void
    {
        $this->testClass->setArguments($arguments);

        $this->assertSame($expectedResponse, $this->testClass->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with boolean - true' => [[true], ['REPLACE']],
            'with boolean - false' => [[false], []],
            'with non boolean' => [['string'], ['string']],
        ];
    }
}
