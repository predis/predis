<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Processor;

use Predis\Command\RawCommand;
use PredisTestCase;

/**
 *
 */
class KeyPrefixProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithPrefix()
    {
        $prefix = 'prefix:';
        $processor = new KeyPrefixProcessor($prefix);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $processor);
        $this->assertEquals($prefix, $processor->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testChangePrefix()
    {
        $prefix1 = 'prefix:';
        $prefix2 = 'prefix:new:';

        $processor = new KeyPrefixProcessor($prefix1);
        $this->assertEquals($prefix1, $processor->getPrefix());

        $processor->setPrefix($prefix2);
        $this->assertEquals($prefix2, $processor->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testProcessPrefixableCommandInterface()
    {
        $prefix = 'prefix:';

        $command = $this->getMock('Predis\Command\PrefixableCommandInterface');
        $command->expects($this->never())->method('getId');
        $command->expects($this->once())->method('prefixKeys')->with($prefix);

        $processor = new KeyPrefixProcessor($prefix);

        $processor->process($command);
    }

    /**
     * @group disconnected
     */
    public function testSkipNotPrefixableCommands()
    {
        $command = $this->getMock('Predis\Command\CommandInterface');
        $command->expects($this->once())
                ->method('getId')
                ->will($this->returnValue('unknown'));
        $command->expects($this->never())
                ->method('getArguments');

        $processor = new KeyPrefixProcessor('prefix');

        $processor->process($command);
    }

    /**
     * @group disconnected
     */
    public function testInstanceCanBeCastedToString()
    {
        $prefix = 'prefix:';
        $processor = new KeyPrefixProcessor($prefix);

        $this->assertEquals($prefix, (string) $processor);
    }

    /**
     * @group disconnected
     */
    public function testPrefixFirst()
    {
        $arguments = array('1st', '2nd', '3rd', '4th');
        $expected = array('prefix:1st', '2nd', '3rd', '4th');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::first($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::skipLast($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixAll()
    {
        $arguments = array('1st', '2nd', '3rd', '4th');
        $expected = array('prefix:1st', 'prefix:2nd', 'prefix:3rd', 'prefix:4th');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::all($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::skipLast($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixInterleaved()
    {
        $arguments = array('1st', '2nd', '3rd', '4th');
        $expected = array('prefix:1st', '2nd', 'prefix:3rd', '4th');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::interleaved($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::skipLast($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixSkipLast()
    {
        $arguments = array('1st', '2nd', '3rd', '4th');
        $expected = array('prefix:1st', 'prefix:2nd', 'prefix:3rd', '4th');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::skipLast($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::skipLast($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixSort()
    {
        $arguments = array('key', 'BY', 'by_key_*', 'STORE', 'destination_key');
        $expected = array('prefix:key', 'BY', 'prefix:by_key_*', 'STORE', 'prefix:destination_key');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::sort($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::sort($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixZSetStore()
    {
        $arguments = array('key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum');
        $expected = array(
            'prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum',
        );

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::zsetStore($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::zsetStore($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixEval()
    {
        $arguments = array('return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'foo', 'hoge', 'bar', 'piyo');
        $expected = array(
            'return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo',
        );

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::evalKeys($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::evalKeys($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixMigrate()
    {
        $arguments = array('127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE');
        $expected = array('127.0.0.1', '6379', 'prefix:key', '0', '10', 'COPY', 'REPLACE');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::migrate($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::sort($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider commandArgumentsDataProvider
     *
     * @param string $commandID
     * @param array  $arguments
     * @param array  $expected
     */
    public function testApplyPrefixToCommand($commandID, array $arguments, array $expected)
    {
        $processor = new KeyPrefixProcessor('prefix:');
        $command = $this->getCommandInstance($commandID, $arguments);

        $processor->process($command);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanDefineNewCommandHandlers()
    {
        $command = $this->getCommandInstance('NEWCMD', array('key', 'value'));

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($command, 'prefix:')
                 ->will($this->returnCallback(function ($command, $prefix) {
                    $command->setRawArguments(array('prefix:key', 'value'));
                 }));

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('NEWCMD', $callable);
        $processor->process($command);

        $this->assertSame(array('prefix:key', 'value'), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanOverrideExistingCommandHandlers()
    {
        $command = $this->getCommandInstance('SET', array('key', 'value'));

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($command, 'prefix:')
                 ->will($this->returnCallback(function ($command, $prefix) {
                    $command->setRawArguments(array('prefix:key', 'value'));
                 }));

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('SET', $callable);
        $processor->process($command);

        $this->assertSame(array('prefix:key', 'value'), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanUndefineCommandHandlers()
    {
        $command = $this->getCommandInstance('SET', array('key', 'value'));

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('SET', null);
        $processor->process($command);

        $this->assertSame(array('key', 'value'), $command->getArguments());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    public function getCommandInstance($commandID, array $arguments)
    {
        $command = new RawCommand(array($commandID));
        $command->setRawArguments($arguments);

        return $command;
    }

    /**
     * Data provider for key prefixing test.
     *
     * @return array
     */
    public function commandArgumentsDataProvider()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */
            array('EXISTS',
                array('key'),
                array('prefix:key'),
            ),
            array('DEL',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            array('TYPE',
                array('key'),
                array('prefix:key'),
            ),
            array('KEYS',
                array('pattern'),
                array('prefix:pattern'),
            ),
            array('RENAME',
                array('key', 'newkey'),
                array('prefix:key', 'prefix:newkey'),
            ),
            array('RENAMENX',
                array('key', 'newkey'),
                array('prefix:key', 'prefix:newkey'),
            ),
            array('EXPIRE',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('EXPIREAT',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('TTL',
                array('key', 10),
                array('prefix:key', 10),
            ),
            array('MOVE',
                array('key', 'db'),
                array('prefix:key', 'db'),
            ),
            array('SORT',
                array('key'),
                array('prefix:key'),
            ),
            array('SORT',
                array('key', 'BY', 'by_key_*'),
                array('prefix:key', 'BY', 'prefix:by_key_*'),
            ),
            array('SORT',
                array('key', 'BY', 'by_key_*', 'STORE', 'destination_key'),
                array('prefix:key', 'BY', 'prefix:by_key_*', 'STORE', 'prefix:destination_key'),
            ),
            array('SORT',
                array('key', 'BY', 'by_key_*', 'GET', 'object_*', 'GET', '#', 'LIMIT', 1, 4, 'ASC', 'ALPHA', 'STORE', 'destination_key'),
                array('prefix:key', 'BY', 'prefix:by_key_*', 'GET', 'prefix:object_*', 'GET', '#', 'LIMIT', 1, 4, 'ASC', 'ALPHA', 'STORE', 'prefix:destination_key'),
            ),
            array('DUMP',
                array('key'),
                array('prefix:key'),
            ),
            array('RESTORE',
                array('key', 0, "\x00\xC0\n\x06\x00\xF8r?\xC5\xFB\xFB_("),
                array('prefix:key', 0, "\x00\xC0\n\x06\x00\xF8r?\xC5\xFB\xFB_("),
            ),
            array('SET',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('SET',
                array('key', 'value', 'EX', 10, 'NX'),
                array('prefix:key', 'value', 'EX', 10, 'NX'),
            ),
            array('SETNX',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('MSET',
                array('foo', 'bar', 'hoge', 'piyo'),
                array('prefix:foo', 'bar', 'prefix:hoge', 'piyo'),
            ),
            array('MSETNX',
                array('foo', 'bar', 'hoge', 'piyo'),
                array('prefix:foo', 'bar', 'prefix:hoge', 'piyo'),
            ),
            array('GET',
                array('key'),
                array('prefix:key'),
            ),
            array('MGET',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            array('GETSET',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('INCR',
                array('key'),
                array('prefix:key'),
            ),
            array('INCRBY',
                array('key', 5),
                array('prefix:key', 5),
            ),
            array('DECR',
                array('key'),
                array('prefix:key'),
            ),
            array('DECRBY',
                array('key', 5),
                array('prefix:key', 5),
            ),
            array('RPUSH',
                array('key', 'value1', 'value2', 'value3'),
                array('prefix:key', 'value1', 'value2', 'value3'),
            ),
            array('LPUSH',
                array('key', 'value1', 'value2', 'value3'),
                array('prefix:key', 'value1', 'value2', 'value3'),
            ),
            array('LLEN',
                array('key'),
                array('prefix:key'),
            ),
            array('LRANGE',
                array('key', 0, -1),
                array('prefix:key', 0, -1),
            ),
            array('LTRIM',
                array('key', 0, 1),
                array('prefix:key', 0, 1),
            ),
            array('LINDEX',
                array('key', 1),
                array('prefix:key', 1),
            ),
            array('LSET',
                array('key', 0, 'value'),
                array('prefix:key', 0, 'value'),
            ),
            array('LREM',
                array('key', 0, 'value'),
                array('prefix:key', 0, 'value'),
            ),
            array('LPOP',
                array('key'),
                array('prefix:key'),
            ),
            array('RPOP',
                array('key'),
                array('prefix:key'),
            ),
            array('RPOPLPUSH',
                array('key:source', 'key:destination'),
                array('prefix:key:source', 'prefix:key:destination'),
            ),
            array('SADD',
                array('key', 'member1', 'member2', 'member3'),
                array('prefix:key', 'member1', 'member2', 'member3'),
            ),
            array('SREM',
                array('key', 'member1', 'member2', 'member3'),
                array('prefix:key', 'member1', 'member2', 'member3'),
            ),
            array('SPOP',
                array('key'),
                array('prefix:key'),
            ),
            array('SMOVE',
                array('key:source', 'key:destination', 'member'),
                array('prefix:key:source', 'prefix:key:destination', 'member'),
            ),
            array('SCARD',
                array('key'),
                array('prefix:key'),
            ),
            array('SISMEMBER',
                array('key', 'member'),
                array('prefix:key', 'member'),
            ),
            array('SINTER',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            array('SINTERSTORE',
                array('key:destination', 'key1', 'key2'),
                array('prefix:key:destination', 'prefix:key1', 'prefix:key2'),
            ),
            array('SUNION',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            array('SUNIONSTORE',
                array('key:destination', 'key1', 'key2'),
                array('prefix:key:destination', 'prefix:key1', 'prefix:key2'),
            ),
            array('SDIFF',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            array('SDIFFSTORE',
                array('key:destination', 'key1', 'key2'),
                array('prefix:key:destination', 'prefix:key1', 'prefix:key2'),
            ),
            array('SMEMBERS',
                array('key'),
                array('prefix:key'),
            ),
            array('SRANDMEMBER',
                array('key', 1),
                array('prefix:key', 1),
            ),
            array('ZADD',
                array('key', 'score1', 'member1', 'score2', 'member2'),
                array('prefix:key', 'score1', 'member1', 'score2', 'member2'),
            ),
            array('ZINCRBY',
                array('key', 1.0, 'member'),
                array('prefix:key', 1.0, 'member'),
            ),
            array('ZREM',
                array('key', 'member1', 'member2', 'member3'),
                array('prefix:key', 'member1', 'member2', 'member3'),
            ),
            array('ZRANGE',
                array('key', 0, 100, 'WITHSCORES'),
                array('prefix:key', 0, 100, 'WITHSCORES'),
            ),
            array('ZREVRANGE',
                array('key', 0, 100, 'WITHSCORES'),
                array('prefix:key', 0, 100, 'WITHSCORES'),
            ),
            array('ZRANGEBYSCORE',
                array('key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'),
                array('prefix:key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'),
            ),
            array('ZCARD',
                array('key'),
                array('prefix:key'),
            ),
            array('ZSCORE',
                array('key', 'member'),
                array('prefix:key', 'member'),
            ),
            array('ZREMRANGEBYSCORE',
                array('key', 0, 10),
                array('prefix:key', 0, 10),
            ),
            /* ---------------- Redis 2.0 ---------------- */
            array('SETEX',
                array('key', 10, 'value'),
                array('prefix:key', 10, 'value'),
            ),
            array('APPEND',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('SUBSTR',
                array('key', 5, 10),
                array('prefix:key', 5, 10),
            ),
            array('BLPOP',
                array('key1', 'key2', 'key3', 10),
                array('prefix:key1', 'prefix:key2', 'prefix:key3', 10),
            ),
            array('BRPOP',
                array('key1', 'key2', 'key3', 10),
                array('prefix:key1', 'prefix:key2', 'prefix:key3', 10),
            ),
            array('ZUNIONSTORE',
                array('key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'),
                array('prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'),
            ),
            array('ZINTERSTORE',
                array('key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'),
                array('prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'),
            ),
            array('ZCOUNT',
                array('key', 0, 10),
                array('prefix:key', 0, 10),
            ),
            array('ZRANK',
                array('key', 'member'),
                array('prefix:key', 'member'),
            ),
            array('ZREVRANK',
                array('key', 'member'),
                array('prefix:key', 'member'),
            ),
            array('ZREMRANGEBYRANK',
                array('key', 0, 10),
                array('prefix:key', 0, 10),
            ),
            array('HSET',
                array('key', 'field', 'value'),
                array('prefix:key', 'field', 'value'),
            ),
            array('HSETNX',
                array('key', 'field', 'value'),
                array('prefix:key', 'field', 'value'),
            ),
            array('HMSET',
                array('key', 'field1', 'value1', 'field2', 'value2'),
                array('prefix:key', 'field1', 'value1', 'field2', 'value2'),
            ),
            array('HINCRBY',
                array('key', 'field', 10),
                array('prefix:key', 'field', 10),
            ),
            array('HGET',
                array('key', 'field'),
                array('prefix:key', 'field'),
            ),
            array('HMGET',
                array('key', 'field1', 'field2', 'field3'),
                array('prefix:key', 'field1', 'field2', 'field3'),
            ),
            array('HDEL',
                array('key', 'field1', 'field2', 'field3'),
                array('prefix:key', 'field1', 'field2', 'field3'),
            ),
            array('HEXISTS',
                array('key', 'field'),
                array('prefix:key', 'field'),
            ),
            array('HLEN',
                array('key'),
                array('prefix:key'),
            ),
            array('HKEYS',
                array('key'),
                array('prefix:key'),
            ),
            array('HVALS',
                array('key'),
                array('prefix:key'),
            ),
            array('HGETALL',
                array('key'),
                array('prefix:key'),
            ),
            array('SUBSCRIBE',
                array('channel:foo', 'channel:hoge'),
                array('prefix:channel:foo', 'prefix:channel:hoge'),
            ),
            array('UNSUBSCRIBE',
                array('channel:foo', 'channel:hoge'),
                array('prefix:channel:foo', 'prefix:channel:hoge'),
            ),
            array('PSUBSCRIBE',
                array('channel:foo:*', 'channel:hoge:*'),
                array('prefix:channel:foo:*', 'prefix:channel:hoge:*'),
            ),
            array('PUNSUBSCRIBE',
                array('channel:foo:*', 'channel:hoge:*'),
                array('prefix:channel:foo:*', 'prefix:channel:hoge:*'),
            ),
            array('PUBLISH',
                array('channel', 'message'),
                array('prefix:channel', 'message'),
            ),
            /* ---------------- Redis 2.2 ---------------- */
            array('PERSIST',
                array('key'),
                array('prefix:key'),
            ),
            array('STRLEN',
                array('key'),
                array('prefix:key'),
            ),
            array('SETRANGE',
                array('key', 5, 'string'),
                array('prefix:key', 5, 'string'),
            ),
            array('GETRANGE',
                array('key', 5, 10),
                array('prefix:key', 5, 10),
            ),
            array('SETBIT',
                array('key', 7, 1),
                array('prefix:key', 7, 1),
            ),
            array('GETBIT',
                array('key', 100),
                array('prefix:key', 100),
            ),
            array('RPUSHX',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('LPUSHX',
                array('key', 'value'),
                array('prefix:key', 'value'),
            ),
            array('LINSERT',
                array('key', 'before', 'value1', 'value2'),
                array('prefix:key', 'before', 'value1', 'value2'),
            ),
            array('BRPOPLPUSH',
                array('key:source', 'key:destination', 10),
                array('prefix:key:source', 'prefix:key:destination', 10),
            ),
            array('ZREVRANGEBYSCORE',
                array('key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'),
                array('prefix:key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'),
            ),
            array('WATCH',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            /* ---------------- Redis 2.6 ---------------- */
            array('PTTL',
                array('key', 10),
                array('prefix:key', 10),
            ),
            array('PEXPIRE',
                array('key', 1500),
                array('prefix:key', 1500),
            ),
            array('PEXPIREAT',
                array('key', 1555555555005),
                array('prefix:key', 1555555555005),
            ),
            array('PSETEX',
                array('key', 1500, 'value'),
                array('prefix:key', 1500, 'value'),
            ),
            array('INCRBYFLOAT',
                array('key', 10.5),
                array('prefix:key', 10.5),
            ),
            array('BITOP',
                array('AND', 'key:dst', 'key:01', 'key:02'),
                array('AND', 'prefix:key:dst', 'prefix:key:01', 'prefix:key:02'),
            ),
            array('BITCOUNT',
                array('key', 0, 10),
                array('prefix:key', 0, 10),
            ),
            array('HINCRBYFLOAT',
                array('key', 'field', 10.5),
                array('prefix:key', 'field', 10.5),
            ),
            array('EVAL',
                array('return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'foo', 'hoge', 'bar', 'piyo'),
                array('return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo'),
            ),
            array('EVALSHA',
                array('a42059b356c875f0717db19a51f6aaca9ae659ea', 2, 'foo', 'hoge', 'bar', 'piyo'),
                array('a42059b356c875f0717db19a51f6aaca9ae659ea', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo'),
            ),
            array('BITPOS',
                array('key', 0),
                array('prefix:key', 0),
            ),
            array('MIGRATE',
                array('127.0.0.1', '6379', 'key', '0', '10'),
                array('127.0.0.1', '6379', 'prefix:key', '0', '10'),
            ),
            /* ---------------- Redis 2.8 ---------------- */
            array('SSCAN',
                array('key', '0', 'MATCH', 'member:*', 'COUNT', 10),
                array('prefix:key', '0', 'MATCH', 'member:*', 'COUNT', 10),
            ),
            array('ZSCAN',
                array('key', '0', 'MATCH', 'member:*', 'COUNT', 10),
                array('prefix:key', '0', 'MATCH', 'member:*', 'COUNT', 10),
            ),
            array('HSCAN',
                array('key', '0', 'MATCH', 'field:*', 'COUNT', 10),
                array('prefix:key', '0', 'MATCH', 'field:*', 'COUNT', 10),
            ),
            array('PFADD',
                array('key', 'a', 'b', 'c'),
                array('prefix:key', 'a', 'b', 'c'),
            ),
            array('PFCOUNT',
                array('key:1', 'key:2', 'key:3'),
                array('prefix:key:1', 'prefix:key:2', 'prefix:key:3'),
            ),
            array('PFMERGE',
                array('key:1', 'key:2', 'key:3'),
                array('prefix:key:1', 'prefix:key:2', 'prefix:key:3'),
            ),
            array('ZLEXCOUNT',
                array('key', '-', '+'),
                array('prefix:key', '-', '+'),
            ),
            array('ZRANGEBYLEX',
                array('key', '-', '+', 'LIMIT', '0', '10'),
                array('prefix:key', '-', '+', 'LIMIT', '0', '10'),
            ),
            array('ZREMRANGEBYLEX',
                array('key', '-', '+'),
                array('prefix:key', '-', '+'),
            ),
            array('ZREVRANGEBYLEX',
                array('key', '+', '-', 'LIMIT', '0', '10'),
                array('prefix:key', '+', '-', 'LIMIT', '0', '10'),
            ),
            /* ---------------- Redis 3.0 ---------------- */
            array('MIGRATE',
                array('127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'),
                array('127.0.0.1', '6379', 'prefix:key', '0', '10', 'COPY', 'REPLACE'),
            ),
            array('EXISTS',
                array('key1', 'key2', 'key3'),
                array('prefix:key1', 'prefix:key2', 'prefix:key3'),
            ),
            /* ---------------- Redis 3.2 ---------------- */
            array('HSTRLEN',
                array('key', 'field'),
                array('prefix:key', 'field'),
            ),
            array('BITFIELD',
                array('key', 'GET', 'u8', '0', 'SET', 'u8', '0', '1'),
                array('prefix:key', 'GET', 'u8', '0', 'SET', 'u8', '0', '1'),
            ),
            array('GEOADD',
                array('key', '13.361389', '38.115556', 'member:1', '15.087269', '37.502669', 'member:2'),
                array('prefix:key', '13.361389', '38.115556', 'member:1', '15.087269', '37.502669', 'member:2'),
            ),
            array('GEOHASH',
                array('key', 'member:1', 'member:2'),
                array('prefix:key', 'member:1', 'member:2'),
            ),
            array('GEOPOS',
                array('key', 'member:1', 'member:2'),
                array('prefix:key', 'member:1', 'member:2'),
            ),
            array('GEODIST',
                array('key', 'member:1', 'member:2', 'km'),
                array('prefix:key', 'member:1', 'member:2', 'km'),
            ),
            array('GEORADIUS',
                array('key', '15', '37', '200', 'km'),
                array('prefix:key', '15', '37', '200', 'km'),
            ),
            array('GEORADIUS',
                array('key', '15', '37', '200', 'km', 'WITHDIST', 'STORE', 'key:store', 'STOREDIST', 'key:storedist'),
                array('prefix:key', '15', '37', '200', 'km', 'WITHDIST', 'STORE', 'prefix:key:store', 'STOREDIST', 'prefix:key:storedist'),
            ),
            array('GEORADIUSBYMEMBER',
                array('key', 'member', '100', 'km'),
                array('prefix:key', 'member', '100', 'km'),
            ),
            array('GEORADIUSBYMEMBER',
                array('key', 'member', '100', 'km', 'WITHDIST', 'STORE', 'key:store', 'STOREDIST', 'key:storedist'),
                array('prefix:key', 'member', '100', 'km', 'WITHDIST', 'STORE', 'prefix:key:store', 'STOREDIST', 'prefix:key:storedist'),
            ),
        );
    }
}
