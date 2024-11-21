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

namespace Predis\Command\Redis;

use UnexpectedValueException;

/**
 * @group commands
 * @group realm-string
 */
class GETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return GETEX::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GETEX';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider keysProvider
     * @param  array  $kvPair
     * @param  array  $arguments
     * @param  string $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsValueAndSetExpirationTimeForGivenKey(
        array $kvPair,
        array $arguments,
        string $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->set(...$kvPair);

        $this->assertSame($expectedResponse, $redis->getex(...$arguments));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->getex(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with EX modifier' => [
                ['key', 'ex', 1],
                ['key', 'EX', 1],
            ],
            'with PX modifier' => [
                ['key', 'px', 1],
                ['key', 'PX', 1],
            ],
            'with EXAT modifier' => [
                ['key', 'exat', 1],
                ['key', 'EXAT', 1],
            ],
            'with PXAT modifier' => [
                ['key', 'pxat', 1],
                ['key', 'PXAT', 1],
            ],
            'with PERSIST modifier' => [
                ['key', 'persist'],
                ['key', 'PERSIST'],
            ],
        ];
    }

    public function keysProvider(): array
    {
        return [
            'without expiration time' => [
                ['key', 'value'],
                ['key', '', false],
                'value',
            ],
            'with expiration - EX modifier' => [
                ['key', 'value'],
                ['key', 'ex', 10],
                'value',
            ],
            'with expiration - PX modifier' => [
                ['key', 'value'],
                ['key', 'px', 10],
                'value',
            ],
            'with expiration - EXAT modifier' => [
                ['key', 'value'],
                ['key', 'exat', 10],
                'value',
            ],
            'with expiration - PXAT modifier' => [
                ['key', 'value'],
                ['key', 'pxat', 10],
                'value',
            ],
            'with expiration - PERSIST modifier' => [
                ['key', 'value'],
                ['key', 'persist'],
                'value',
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong modifier' => [
                ['key', 'wrong', 1],
                'Modifier argument accepts only: ex, px, exat, pxat, persist values',
            ],
            'without value provided' => [
                ['key', 'ex'],
                'You should provide value for current modifier',
            ],
        ];
    }
}
