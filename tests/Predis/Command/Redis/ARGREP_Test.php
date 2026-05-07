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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-array
 */
class ARGREP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARGREP::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARGREP';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actual, array $expected): void
    {
        $command = $this->getCommand();
        $command->setArguments($actual);

        $this->assertSame($expected, $command->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'single EXACT predicate' => [
                ['key', 0, 10, [['EXACT', 'foo']]],
                ['key', 0, 10, 'EXACT', 'foo'],
            ],
            'multiple predicates with AND' => [
                ['key', 0, 10, [['EXACT', 'foo'], ['MATCH', 'bar']], 'AND'],
                ['key', 0, 10, 'EXACT', 'foo', 'MATCH', 'bar', 'AND'],
            ],
            'with LIMIT' => [
                ['key', 0, 10, [['GLOB', '*foo*']], null, 5],
                ['key', 0, 10, 'GLOB', '*foo*', 'LIMIT', 5],
            ],
            'with WITHVALUES and NOCASE' => [
                ['key', 0, 10, [['RE', '^foo$']], null, null, true, true],
                ['key', 0, 10, 'RE', '^foo$', 'WITHVALUES', 'NOCASE'],
            ],
            'all options' => [
                ['key', 0, 10, [['EXACT', 'a'], ['MATCH', 'b']], 'OR', 3, true, true],
                ['key', 0, 10, 'EXACT', 'a', 'MATCH', 'b', 'OR', 'LIMIT', 3, 'WITHVALUES', 'NOCASE'],
            ],
        ];
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidPredicateType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', 0, 10, [['INVALID', 'value']]]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidCombinator(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', 0, 10, [['EXACT', 'a']], 'INVALID']);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame([0, 2], $this->getCommand()->parseResponse([0, 2]));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 10, 'EXACT', 'foo'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 10, 'EXACT', 'foo'];

        $command->setRawArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsExactMatchIndices(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'bar', 'foo', 'baz');

        $this->assertSame([0, 2], $redis->argrep('arr', 0, 3, [['EXACT', 'foo']]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsSubstringMatchIndices(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foobar', 'foo', 'barbaz');

        $this->assertSame([0, 2], $redis->argrep('arr', 0, 2, [['MATCH', 'bar']]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsGlobMatchIndices(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'bar', 'foobar');

        $this->assertSame([0, 2], $redis->argrep('arr', 0, 2, [['GLOB', 'foo*']]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsRegexMatchIndices(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'bar', 'baz');

        $this->assertSame([1, 2], $redis->argrep('arr', 0, 2, [['RE', '^ba']]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNoCaseMakesSearchCaseInsensitive(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'FOO', 'foo', 'Bar');

        $this->assertSame(
            [0, 1],
            $redis->argrep('arr', 0, 2, [['EXACT', 'foo']], null, null, false, true)
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testWithValuesReturnsIndexValuePairs(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'bar');

        $this->assertSame(
            [0, 'foo'],
            $redis->argrep('arr', 0, 1, [['EXACT', 'foo']], null, null, true)
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testLimitCapsResults(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'foo', 'foo', 'foo');

        $this->assertSame(
            [0, 1],
            $redis->argrep('arr', 0, 3, [['EXACT', 'foo']], null, 2)
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsEmptyArrayForMissingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame([], $redis->argrep('nonexistent', 0, 10, [['EXACT', 'foo']]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsExactMatchIndicesResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, 'foo', 'bar', 'foo');

        $this->assertSame([0, 2], $redis->argrep('arr', 0, 2, [['EXACT', 'foo']]));
    }
}
