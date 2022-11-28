<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use UnexpectedValueException;

class KeysTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class {
            use Keys;
        };
    }

    /**
     * @dataProvider keysProvider
     * @param int $offset
     * @param array $actualArguments
     * @param array $unpackedArguments
     * @return void
     */
    public function testUnpackKeysArrayTransformArrayCorrectly(
        int $offset,
        array $actualArguments,
        array $unpackedArguments
    ): void {
        $this->testClass->unpackKeysArray($offset, $actualArguments);

        $this->assertSame($unpackedArguments, $actualArguments);
    }

    /**
     * @dataProvider unexpectedValuesProvider
     * @param int $offset
     * @param array $actualArguments
     * @return void
     */
    public function testUnpackKeysArrayThrowsExceptionOnUnexpectedValueGiven(int $offset, array $actualArguments): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong keys argument type or position offset');

        $this->testClass->unpackKeysArray($offset, $actualArguments);
    }

    public function keysProvider(): array
    {
        return [
            'keys argument first and there is arguments after' => [
                0,
                [['key1', 'key2'], 'second argument', 'third argument'],
                ['key1', 'key2', 'second argument', 'third argument'],
            ],
            'keys argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', ['key1', 'key2']],
                ['first argument', 'second argument', 'key1', 'key2'],
            ],
            'keys argument not the first and not the last' => [
                1,
                ['first argument', ['key1', 'key2'], 'third argument'],
                ['first argument', 'key1', 'key2', 'third argument'],
            ],
            'keys argument the only argument' => [
                0,
                [['key1', 'key2']],
                ['key1', 'key2'],
            ]
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'keys argument not an array' => [
                0,
                ['key1'],
            ],
            'keys argument position offset higher then arguments quantity' => [
                2,
                [['key1', 'key2']],
            ]
        ];
    }
}
