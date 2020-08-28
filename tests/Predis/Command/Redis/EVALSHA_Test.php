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
class EVALSHA_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\EVALSHA';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EVALSHA';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('9d0c0826bde023cc39eebaaf832c32a890f3b088', 1, 'foo', 'bar');
        $expected = array('9d0c0826bde023cc39eebaaf832c32a890f3b088', 1, 'foo', 'bar');

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
        $command = $this->getCommandWithArgumentsArray(array($sha1 = sha1('return true')), 0);
        $this->assertSame($sha1, $command->getScriptHash());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testExecutesSpecifiedLuaScript(): void
    {
        $redis = $this->getClient();

        $lua = 'return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}';
        $sha1 = sha1($lua);
        $result = array('foo', 'hoge', 'bar', 'piyo');

        $this->assertSame($result, $redis->eval($lua, 2, 'foo', 'hoge', 'bar', 'piyo'));
        $this->assertSame($result, $redis->evalsha($sha1, 2, 'foo', 'hoge', 'bar', 'piyo'));
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
        $sha1 = sha1($lua);

        $redis->eval($lua, 2, 'foo', 'hoge', 'bar', 'piyo');
        $redis->evalsha($sha1, 3, 'foo', 'hoge');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnInvalidScript(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->evalsha('ffffffffffffffffffffffffffffffffffffffff', 0);
    }
}
