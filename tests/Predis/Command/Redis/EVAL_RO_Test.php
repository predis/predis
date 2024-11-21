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

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-scripting
 */
class EVAL_RO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return EVAL_RO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EVAL_RO';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ["return redis.call('GET', KEYS[1])", ['key1', 'key2'], 'arg1', 'arg2'];
        $expected = ["return redis.call('GET', KEYS[1])", 2, 'key1', 'key2', 'arg1', 'arg2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider scriptsProvider
     * @param  array  $dictionary
     * @param  string $script
     * @param  array  $keys
     * @param  array  $arguments
     * @param         $expectedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testExecutesReadOnlyCommandsFromGivenLuaScript(
        array $dictionary,
        string $script,
        array $keys,
        array $arguments,
        $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->mset(...$dictionary);

        $this->assertSame($expectedResponse, $redis->eval_ro($script, $keys, ...$arguments));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsErrorOnWriteCommandProvided(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessageMatches('/^ERR Write commands are not allowed from read-only scripts./');

        $redis->eval_ro("return redis.call('SET', KEYS[1], ARGV[1])", ['key'], 'value');
    }

    public function scriptsProvider(): array
    {
        return [
            'with single key' => [
                ['key', 'value'],
                "return redis.call('GET', KEYS[1])",
                ['key'],
                [],
                'value',
            ],
            'with multiple keys' => [
                ['key', 'value', 'key1', 2],
                "return redis.call('MGET', KEYS[1], KEYS[2])",
                ['key', 'key1'],
                [],
                ['value', '2'],
            ],
            'with arguments provided' => [
                ['key', 'mytest', 'key1', 'ourtest'],
                "return redis.call('LCS', KEYS[1], KEYS[2], ARGV[1])",
                ['key', 'key1'],
                ['LEN'],
                4,
            ],
        ];
    }
}
