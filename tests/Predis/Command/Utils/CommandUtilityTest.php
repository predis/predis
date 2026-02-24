<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Utils;

use Predis\Command\Redis\Utils\CommandUtility;
use PredisTestCase;
use RuntimeException;
use UnexpectedValueException;

class CommandUtilityTest extends PredisTestCase
{
    /**
     * @group disconnected
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
     * @group disconnected
     * @return void
     */
    public function testArrayToDictionaryThrowsExceptionOnOddNumberOfElements()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Array must have an even number of arguments');

        CommandUtility::arrayToDictionary(['key1', 'value1', 'key1']);
    }

    /**
     * @group disconnected
     * @requires PHP >= 8.1
     * @return void
     */
    public function testXXH3Hash()
    {
        $expectedHash = '87d57e269b9df0f0';

        $this->assertEquals($expectedHash, CommandUtility::xxh3Hash('value'));
    }

    /**
     * @group disconnected
     * @requires PHP < 8.1
     * @return void
     */
    public function testXXH3HashRaiseExceptionOnPHPLowerThan81()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('XXH3 algorithm is not supported. Please install PECL xxhash extension.');

        CommandUtility::xxh3Hash('value');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDictionaryToArray(): void
    {
        $dict = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $this->assertSame(
            ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
            CommandUtility::dictionaryToArray($dict)
        );
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
