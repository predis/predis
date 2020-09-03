<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

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
        $arguments = array('AND', 'key:dst', 'key:01', 'key:02');
        $expected = array('AND', 'key:dst', 'key:01', 'key:02');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsKeysAsSingleArray(): void
    {
        $arguments = array('AND', 'key:dst', array('key:01', 'key:02'));
        $expected = array('AND', 'key:dst', 'key:01', 'key:02');

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
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnInvalidOperation(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR syntax error');

        $this->getClient()->bitop('NOOP', 'key:dst', 'key:src:1', 'key:src:2');
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
