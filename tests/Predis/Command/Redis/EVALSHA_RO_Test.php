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
class EVALSHA_RO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return EVALSHA_RO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EVALSHA_RO';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['return test', ['key1', 'key2'], 'arg1', 'arg2'];
        $expected = ['return test', 2, 'key1', 'key2', 'arg1', 'arg2'];

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
     * @param  string $script
     * @param  array  $keys
     * @param  array  $arguments
     * @param         $expectedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testExecutesReadOnlyCachedScripts(
        string $script,
        array $keys,
        array $arguments,
        $expectedResponse
    ): void {
        $redis = $this->getClient();

        $sha1 = $redis->script('LOAD', $script);

        $this->assertSame($expectedResponse, $redis->evalsha_ro($sha1, $keys, ...$arguments));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsErrorOnWriteScriptExecution(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessageMatches('/^ERR Write commands are not allowed from read-only scripts./');

        $sha1 = $redis->script('LOAD', "return redis.call('SET', KEYS[1], ARGV[1])");

        $redis->evalsha_ro($sha1, ['key'], 'value');
    }

    public function scriptsProvider(): array
    {
        return [
            'with required arguments' => [
                "return 'test'",
                [],
                [],
                'test',
            ],
            'with keys argument' => [
                "return 'test ' .. KEYS[1] .. ' ' .. KEYS[2]",
                ['key1', 'key2'],
                [],
                'test key1 key2',
            ],
            'with arguments provided' => [
                "return 'test ' .. ARGV[1] .. ' ' .. ARGV[2]",
                ['key1', 'key2'],
                ['arg1', 'arg2'],
                'test arg1 arg2',
            ],
            'with both arguments provided' => [
                "return 'test ' .. KEYS[1] .. ' ' .. ARGV[1]",
                ['key1', 'key2'],
                ['arg1', 'arg2'],
                'test key1 arg1',
            ],
        ];
    }
}
