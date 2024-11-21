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

namespace Predis\Command\Traits\Get;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class GetTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Get;

            public static $getArgumentPositionOffset = 0;

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
        $this->testClass::$getArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong get argument type');

        $this->testClass->setArguments(['wrong']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [
                1,
                ['value'],
                ['value'],
            ],
            'with single value' => [
                0,
                [['value']],
                ['GET', 'value'],
            ],
            'with multiple values' => [
                0,
                [['value1', 'value2']],
                ['GET', 'value1', 'GET', 'value2'],
            ],
        ];
    }
}
