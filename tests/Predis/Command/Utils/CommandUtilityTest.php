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

namespace Predis\Command\Utils;

use Predis\Command\Redis\Utils\CommandUtility;
use PredisTestCase;
use UnexpectedValueException;

class CommandUtilityTest extends PredisTestCase
{
    /**
     * @dataProvider arrayProvider
     * @param  array         $actual
     * @param  array         $expected
     * @param  callable|null $callback
     * @param  bool          $recursive
     * @return void
     */
    public function testArrayToDictionary(array $actual, array $expected, ?callable $callback, bool $recursive = true)
    {
        $this->assertSame($expected, CommandUtility::arrayToDictionary($actual, $callback, $recursive));
    }

    /**
     * @return void
     */
    public function testArrayToDictionaryThrowsExceptionOnOddNumberOfElements()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Array must have an even number of arguments');

        CommandUtility::arrayToDictionary(['key1', 'value1', 'key1']);
    }

    public function arrayProvider(): array
    {
        return [
            'without nesting arrays' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                null,
            ],
            'with nesting arrays' => [
                ['key1', ['key2', ['key3', 'value3']]],
                ['key1' => ['key2' => ['key3' => 'value3']]],
                null,
            ],
            'with callback applied' => [
                ['key1', ['key2', ['key3', '0.1']]],
                ['key1' => ['key2' => ['key3' => 0.1]]],
                function ($key, $value) {
                    return [$key, (float) $value];
                },
            ],
            'with non-recursive approach' => [
                ['key1', ['key2', ['key3', '0.1']]],
                ['key1' => ['key2', ['key3', '0.1']]],
                function ($key, $value) {
                    return [$key, (float) $value];
                },
                false,
            ],
        ];
    }
}
