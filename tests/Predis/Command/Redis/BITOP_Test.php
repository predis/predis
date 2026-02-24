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

use InvalidArgumentException;
use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-string
 */
class BITOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\BITOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BITOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['AND', 'key:dst', 'key:01', 'key:02'];
        $expected = ['AND', 'key:dst', 'key:01', 'key:02'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsKeysAsSingleArray(): void
    {
        $arguments = ['AND', 'key:dst', ['key:01', 'key:02']];
        $expected = ['AND', 'key:dst', 'key:01', 'key:02'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = 10;
        $expected = 10;

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['AND', 'arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['AND', 'prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCanPerformBitwiseAND(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "h\x80");
        $redis->set('key:src:2', 'R');

        $this->assertSame(2, $redis->bitop('AND', 'key:dst', 'key:src:1', 'key:src:2'));
        $this->assertSame("@\x00", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testCanPerformBitwiseANDResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->set('key:src:1', "h\x80");
        $redis->set('key:src:2', 'R');

        $this->assertSame(2, $redis->bitop('AND', 'key:dst', 'key:src:1', 'key:src:2'));
        $this->assertSame("@\x00", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCanPerformBitwiseOR(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "h\x80");
        $redis->set('key:src:2', 'R');

        $this->assertSame(2, $redis->bitop('OR', 'key:dst', 'key:src:1', 'key:src:2'));
        $this->assertSame("z\x80", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCanPerformBitwiseXOR(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "h\x80");
        $redis->set('key:src:2', 'R');

        $this->assertSame(2, $redis->bitop('XOR', 'key:dst', 'key:src:1', 'key:src:2'));
        $this->assertSame(":\x80", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.2.0
     */
    public function testCanPerformBitwiseONE(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "\x01");
        $redis->set('key:src:2', "\x02");

        $this->assertSame(1, $redis->bitop('ONE', 'key:dst', 'key:src:1', 'key:src:2'));
        $this->assertSame("\x03", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.2.0
     */
    public function testCanPerformBitwiseDIFF(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "\x01");
        $redis->set('key:src:2', "\x00");
        $redis->set('key:src:3', "\x04");

        $this->assertSame(1, $redis->bitop('DIFF', 'key:dst', 'key:src:1', 'key:src:2', 'key:src:3'));
        $this->assertSame("\x01", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.2.0
     */
    public function testCanPerformBitwiseDIFF1(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "\x01");
        $redis->set('key:src:2', "\x00");
        $redis->set('key:src:3', "\x04");

        $this->assertSame(1, $redis->bitop('DIFF1', 'key:dst', 'key:src:1', 'key:src:2', 'key:src:3'));
        $this->assertSame("\x04", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.2.0
     */
    public function testCanPerformBitwiseANDOR(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "\x03");
        $redis->set('key:src:2', "\x02");
        $redis->set('key:src:3', "\x04");

        $this->assertSame(1, $redis->bitop('ANDOR', 'key:dst', 'key:src:1', 'key:src:2', 'key:src:3'));
        $this->assertSame("\x02", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCanPerformBitwiseNOT(): void
    {
        $redis = $this->getClient();

        $redis->set('key:src:1', "h\x80");

        $this->assertSame(2, $redis->bitop('NOT', 'key:dst', 'key:src:1'));
        $this->assertSame("\x97\x7f", $redis->get('key:dst'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testBitwiseNOTAcceptsOnlyOneSourceKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR BITOP NOT must be called with a single source key');

        $this->getClient()->bitop('NOT', 'key:dst', 'key:src:1', 'key:src:2');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidOperation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BITOP operation must be one of: AND, OR, XOR, NOT, DIFF, DIFF1, ANDOR, ONE');

        $command = $this->getCommand();
        $command->setArguments(['NOOP', 'key:dst', 'key:src:1', 'key:src:2']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnInvalidSourceKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('key:src:1', 'list');
        $redis->bitop('AND', 'key:dst', 'key:src:1', 'key:src:2');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testDoesNotThrowExceptionOnInvalidDestinationKey(): void
    {
        $redis = $this->getClient();

        $redis->lpush('key:dst', 'list');
        $redis->bitop('AND', 'key:dst', 'key:src:1', 'key:src:2');

        $this->assertEquals('none', $redis->type('key:dst'));
    }
}
