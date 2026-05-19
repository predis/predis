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
class AROP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return AROP::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'AROP';
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
            'SUM' => [
                ['key', 0, 5, 'SUM'],
                ['key', 0, 5, 'SUM'],
            ],
            'MIN' => [
                ['key', 0, 5, 'min'],
                ['key', 0, 5, 'MIN'],
            ],
            'MAX' => [
                ['key', 0, 5, 'MAX'],
                ['key', 0, 5, 'MAX'],
            ],
            'AND' => [
                ['key', 0, 5, 'AND'],
                ['key', 0, 5, 'AND'],
            ],
            'OR' => [
                ['key', 0, 5, 'OR'],
                ['key', 0, 5, 'OR'],
            ],
            'XOR' => [
                ['key', 0, 5, 'XOR'],
                ['key', 0, 5, 'XOR'],
            ],
            'MATCH' => [
                ['key', 0, 5, 'MATCH', 'foo'],
                ['key', 0, 5, 'MATCH', 'foo'],
            ],
            'USED' => [
                ['key', 0, 5, 'USED'],
                ['key', 0, 5, 'USED'],
            ],
        ];
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidOperation(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', 0, 5, 'INVALID']);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMatchWithoutValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('MATCH operation requires a value argument');

        $command = $this->getCommand();
        $command->setArguments(['key', 0, 5, 'MATCH']);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('15', $this->getCommand()->parseResponse('15'));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 5, 'SUM'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 5, 'SUM'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsSumOfNumericElements(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, '1', '2', '3', '4');

        $this->assertSame('10', $redis->arop('arr', 0, 3, 'SUM'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsMinOfNumericElements(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, '5', '3', '7', '1');

        $this->assertSame('1', $redis->arop('arr', 0, 3, 'MIN'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsMaxOfNumericElements(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, '5', '3', '7', '1');

        $this->assertSame('7', $redis->arop('arr', 0, 3, 'MAX'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsCountOfMatchingElements(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'foo', 'bar', 'foo', 'baz');

        $this->assertSame(2, $redis->arop('arr', 0, 3, 'MATCH', 'foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsCountOfUsedElements(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b');
        $redis->arset('arr', 5, 'c');

        $this->assertSame(3, $redis->arop('arr', 0, 5, 'USED'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsSumOfNumericElementsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, '1', '2', '3');

        $this->assertSame('6', $redis->arop('arr', 0, 2, 'SUM'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->arop('foo', 0, 5, 'SUM');
    }
}
