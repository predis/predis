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

namespace Predis\Command\Redis;

use Predis\Response\Status;

/**
 * @group commands
 * @group realm-server
 */
class COMMAND_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\COMMAND';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'COMMAND';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['INFO', 'DEL'];
        $expected = ['INFO', 'DEL'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = [
            ['get', 2, [new Status('readonly'), new Status('fast')], 1, 1, 1],
            ['set', -3, [new Status('write'), new Status('denyoom')], 1, 1, 1],
            ['watch', -2, [new Status('readonly'), new Status('noscript'), new Status('fast')], 1, -1, 1],
            ['unwatch', 1, [new Status('readonly'), new Status('noscript'), new Status('fast')], 0, 0, 0],
            ['info', -1, [new Status('readonly'), new Status('loading'), new Status('stale')], 0, 0, 0],
        ];

        $expected = $raw;

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseEmptyResponse(): void
    {
        $raw = [null];
        $expected = [null];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsEmptyCommandInfoOnNonExistingCommand(): void
    {
        $redis = $this->getClient();

        $this->assertCount(1, $response = $redis->command('INFO', 'FOOBAR'));
        $this->assertSame([null], $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsEmptyCommandInfoOnNonExistingCommandResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertCount(1, $response = $redis->command('INFO', 'FOOBAR'));
        $this->assertSame([null], $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     *
     * Relay uses RESP3 maps, the `Predis\Command\Redis\COMMAND` needs a converter.
     */
    public function testReturnsCommandInfoOnExistingCommand(): void
    {
        $redis = $this->getClient();

        $expected = [['get', 2, ['readonly', 'fast'], 1, 1, 1]];

        // NOTE: starting with Redis 6.0 and the introduction of Access Control
        // Lists, COMMAND INFO returns an additional array for each specified
        // command in the request with a list of the ACL categories associated
        // to a command. We simply append this additional array in the expected
        // response if the test suite is executed against Redis >= 6.0.
        if ($this->isRedisServerVersion('>=', '6.0')) {
            $expected[0][] = ['@read', '@string', '@fast'];
        }

        // NOTE: starting with Redis 7.0 COMMAND INFO returns an additional arrays:
        // - Command tips: https://redis.io/topics/command-tips.
        // - Key specifications: https://redis.io/topics/key-specs.
        // - Subcommands: https://redis.io/commands/command/#subcommands.
        // We simply append this additional array in the expected response if the
        // test suite is executed against Redis >= 7.0.
        if ($this->isRedisServerVersion('>=', '7.0')) {
            $expected[0][] = [];
            $expected[0][] = [
                [
                    'flags',
                    ['RO', 'access'],
                    'begin_search',
                    ['type', 'index', 'spec', ['index', 1]],
                    'find_keys',
                    ['type', 'range', 'spec', ['lastkey', 0, 'keystep', 1, 'limit', 0]],
                ],
            ];
            $expected[0][] = [];
        }

        $this->assertCount(1, $response = $redis->command('INFO', 'GET'));

        // NOTE: we use assertEquals instead of assertSame because Redis returns
        // flags as +STATUS responses, represented by Predis with instances of
        // Predis\Response\Status instead of plain strings. This class responds
        // to __toString() so the string conversion is implicit, but assertSame
        // checks for strict equality while assertEquals is loose.
        $this->assertEquals($expected, $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsListOfCommandInfoWithNoArguments(): void
    {
        $redis = $this->getClient();

        $this->assertGreaterThan(100, count($response = $redis->command()));
    }
}
