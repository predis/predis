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
 * @group realm-scripting
 */
class EVAL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\EVAL_';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EVAL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('return redis.call("SET", KEYS[1], ARGV[1])', 1, 'foo', 'bar');
        $expected = array('return redis.call("SET", KEYS[1], ARGV[1])', 1, 'foo', 'bar');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('bar', $this->getCommand()->parseResponse('bar'));
    }

    /**
     * @group disconnected
     */
    public function testGetScriptHash(): void
    {
        $command = $this->getCommandWithArgumentsArray(array($lua = 'return true', 0));
        $this->assertSame(sha1($lua), $command->getScriptHash());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testExecutesSpecifiedLuaScript(): void
    {
        $redis = $this->getClient();

        $lua = 'return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}';
        $result = array('foo', 'hoge', 'bar', 'piyo');

        $this->assertSame($result, $redis->eval($lua, 2, 'foo', 'hoge', 'bar', 'piyo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnWrongNumberOfKeys(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();
        $lua = 'return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}';

        $redis->eval($lua, 3, 'foo', 'hoge');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnInvalidScript(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->eval('invalid', 0);
    }
}
