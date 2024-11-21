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

namespace Predis\Command\Processor;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use PredisTestCase;
use stdClass;

class KeyPrefixProcessorTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithPrefix(): void
    {
        $prefix = 'prefix:';
        $processor = new KeyPrefixProcessor($prefix);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $processor);
        $this->assertEquals($prefix, $processor->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testChangePrefix(): void
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
    public function testProcessPrefixableCommandInterface(): void
    {
        $prefix = 'prefix:';

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\PrefixableCommandInterface')->getMock();
        $command
            ->expects($this->never())
            ->method('getId');
        $command
            ->expects($this->once())
            ->method('prefixKeys')
            ->with($prefix);

        $processor = new KeyPrefixProcessor($prefix);

        $processor->process($command);
    }

    /**
     * @group disconnected
     */
    public function testSkipNotPrefixableCommands(): void
    {
        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command->expects($this->once())
            ->method('getId')
            ->willReturn('unknown');
        $command
            ->expects($this->never())
            ->method('getArguments');

        $processor = new KeyPrefixProcessor('prefix');

        $processor->process($command);
    }

    /**
     * @group disconnected
     */
    public function testInstanceCanBeCastedToString(): void
    {
        $prefix = 'prefix:';
        $processor = new KeyPrefixProcessor($prefix);

        $this->assertEquals($prefix, (string) $processor);
    }

    /**
     * @group disconnected
     */
    public function testPrefixFirst(): void
    {
        $arguments = ['1st', '2nd', '3rd', '4th'];
        $expected = ['prefix:1st', '2nd', '3rd', '4th'];

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
    public function testPrefixFirstTwo(): void
    {
        $arguments = ['1st', '2nd', '3rd', '4th'];
        $expected = ['prefix:1st', 'prefix:2nd', '3rd', '4th'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::firstTwo($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // One argument
        $arguments = ['1st'];
        $expected = ['prefix:1st'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        KeyPrefixProcessor::firstTwo($command, 'prefix:');
        $this->assertSame($expected, $command->getArguments());

        // Empty arguments
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        KeyPrefixProcessor::firstTwo($command, 'prefix:');
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixAll(): void
    {
        $arguments = ['1st', '2nd', '3rd', '4th'];
        $expected = ['prefix:1st', 'prefix:2nd', 'prefix:3rd', 'prefix:4th'];

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
    public function testPrefixInterleaved(): void
    {
        $arguments = ['1st', '2nd', '3rd', '4th'];
        $expected = ['prefix:1st', '2nd', 'prefix:3rd', '4th'];

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
    public function testPrefixSkipLast(): void
    {
        $arguments = ['1st', '2nd', '3rd', '4th'];
        $expected = ['prefix:1st', 'prefix:2nd', 'prefix:3rd', '4th'];

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
    public function testPrefixSort(): void
    {
        $arguments = ['key', 'BY', 'by_key_*', 'STORE', 'destination_key'];
        $expected = ['prefix:key', 'BY', 'prefix:by_key_*', 'STORE', 'prefix:destination_key'];

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
    public function testPrefixZSetStore(): void
    {
        $arguments = ['key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'];
        $expected = [
            'prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum',
        ];

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
    public function testPrefixEval(): void
    {
        $arguments = ['return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'foo', 'hoge', 'bar', 'piyo'];
        $expected = [
            'return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo',
        ];

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
    public function testPrefixMigrate(): void
    {
        $arguments = ['127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'];
        $expected = ['127.0.0.1', '6379', 'prefix:key', '0', '10', 'COPY', 'REPLACE'];

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
    public function testApplyPrefixToCommand($commandID, array $arguments, array $expected): void
    {
        $processor = new KeyPrefixProcessor('prefix:');
        $command = $this->getCommandInstance($commandID, $arguments);

        $processor->process($command);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanDefineNewCommandHandlers(): void
    {
        $command = $this->getCommandInstance('NEWCMD', ['key', 'value']);

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($command, 'prefix:')
            ->willReturnCallback(function ($command, $prefix) {
                $command->setRawArguments(['prefix:key', 'value']);
            });

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('NEWCMD', $callable);
        $processor->process($command);

        $this->assertSame(['prefix:key', 'value'], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanOverrideExistingCommandHandlers(): void
    {
        $command = $this->getCommandInstance('SET', ['key', 'value']);

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($command, 'prefix:')
            ->willReturnCallback(function ($command, $prefix) {
                $command->setRawArguments(['prefix:key', 'value']);
            });

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('SET', $callable);
        $processor->process($command);

        $this->assertSame(['prefix:key', 'value'], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanUndefineCommandHandlers(): void
    {
        $command = $this->getCommandInstance('SET', ['key', 'value']);

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('SET', null);
        $processor->process($command);

        $this->assertSame(['key', 'value'], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCannotDefineCommandHandlerWithInvalidType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Callback must be a valid callable object or NULL');

        $processor = new KeyPrefixProcessor('prefix:');
        $processor->setCommandHandler('NEWCMD', new stdClass());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a command instance by ID populated with the specified arguments.
     *
     * @param string $commandID ID of the Redis command
     * @param array  $arguments List of arguments for the command
     *
     * @return CommandInterface
     */
    public function getCommandInstance(string $commandID, array $arguments): CommandInterface
    {
        $command = new RawCommand($commandID);
        $command->setRawArguments($arguments);

        return $command;
    }

    /**
     * Data provider for key prefixing test.
     *
     * @return array
     */
    public function commandArgumentsDataProvider(): array
    {
        return [
            /* ---------------- Redis 1.2 ---------------- */
            ['EXISTS',
                ['key'],
                ['prefix:key'],
            ],
            ['DEL',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            ['TYPE',
                ['key'],
                ['prefix:key'],
            ],
            ['KEYS',
                ['pattern'],
                ['prefix:pattern'],
            ],
            ['RENAME',
                ['key', 'newkey'],
                ['prefix:key', 'prefix:newkey'],
            ],
            ['RENAMENX',
                ['key', 'newkey'],
                ['prefix:key', 'prefix:newkey'],
            ],
            ['EXPIRE',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['EXPIREAT',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['TTL',
                ['key', 10],
                ['prefix:key', 10],
            ],
            ['MOVE',
                ['key', 'db'],
                ['prefix:key', 'db'],
            ],
            ['SORT',
                ['key'],
                ['prefix:key'],
            ],
            ['SORT',
                ['key', 'BY', 'by_key_*'],
                ['prefix:key', 'BY', 'prefix:by_key_*'],
            ],
            ['SORT',
                ['key', 'BY', 'by_key_*', 'STORE', 'destination_key'],
                ['prefix:key', 'BY', 'prefix:by_key_*', 'STORE', 'prefix:destination_key'],
            ],
            ['SORT',
                ['key', 'BY', 'by_key_*', 'GET', 'object_*', 'GET', '#', 'LIMIT', 1, 4, 'ASC', 'ALPHA', 'STORE', 'destination_key'],
                ['prefix:key', 'BY', 'prefix:by_key_*', 'GET', 'prefix:object_*', 'GET', '#', 'LIMIT', 1, 4, 'ASC', 'ALPHA', 'STORE', 'prefix:destination_key'],
            ],
            ['DUMP',
                ['key'],
                ['prefix:key'],
            ],
            ['RESTORE',
                ['key', 0, "\x00\xC0\n\x06\x00\xF8r?\xC5\xFB\xFB_("],
                ['prefix:key', 0, "\x00\xC0\n\x06\x00\xF8r?\xC5\xFB\xFB_("],
            ],
            ['SET',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['SET',
                ['key', 'value', 'EX', 10, 'NX'],
                ['prefix:key', 'value', 'EX', 10, 'NX'],
            ],
            ['SETNX',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['MSET',
                ['foo', 'bar', 'hoge', 'piyo'],
                ['prefix:foo', 'bar', 'prefix:hoge', 'piyo'],
            ],
            ['MSETNX',
                ['foo', 'bar', 'hoge', 'piyo'],
                ['prefix:foo', 'bar', 'prefix:hoge', 'piyo'],
            ],
            ['GET',
                ['key'],
                ['prefix:key'],
            ],
            ['MGET',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            ['GETSET',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['INCR',
                ['key'],
                ['prefix:key'],
            ],
            ['INCRBY',
                ['key', 5],
                ['prefix:key', 5],
            ],
            ['DECR',
                ['key'],
                ['prefix:key'],
            ],
            ['DECRBY',
                ['key', 5],
                ['prefix:key', 5],
            ],
            ['RPUSH',
                ['key', 'value1', 'value2', 'value3'],
                ['prefix:key', 'value1', 'value2', 'value3'],
            ],
            ['LPUSH',
                ['key', 'value1', 'value2', 'value3'],
                ['prefix:key', 'value1', 'value2', 'value3'],
            ],
            ['LLEN',
                ['key'],
                ['prefix:key'],
            ],
            ['LRANGE',
                ['key', 0, -1],
                ['prefix:key', 0, -1],
            ],
            ['LTRIM',
                ['key', 0, 1],
                ['prefix:key', 0, 1],
            ],
            ['LINDEX',
                ['key', 1],
                ['prefix:key', 1],
            ],
            ['LSET',
                ['key', 0, 'value'],
                ['prefix:key', 0, 'value'],
            ],
            ['LREM',
                ['key', 0, 'value'],
                ['prefix:key', 0, 'value'],
            ],
            ['LPOP',
                ['key'],
                ['prefix:key'],
            ],
            ['RPOP',
                ['key'],
                ['prefix:key'],
            ],
            ['RPOPLPUSH',
                ['key:source', 'key:destination'],
                ['prefix:key:source', 'prefix:key:destination'],
            ],
            ['SADD',
                ['key', 'member1', 'member2', 'member3'],
                ['prefix:key', 'member1', 'member2', 'member3'],
            ],
            ['SREM',
                ['key', 'member1', 'member2', 'member3'],
                ['prefix:key', 'member1', 'member2', 'member3'],
            ],
            ['SPOP',
                ['key'],
                ['prefix:key'],
            ],
            ['SMOVE',
                ['key:source', 'key:destination', 'member'],
                ['prefix:key:source', 'prefix:key:destination', 'member'],
            ],
            ['SCARD',
                ['key'],
                ['prefix:key'],
            ],
            ['SISMEMBER',
                ['key', 'member'],
                ['prefix:key', 'member'],
            ],
            ['SINTER',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            ['SINTERSTORE',
                ['key:destination', 'key1', 'key2'],
                ['prefix:key:destination', 'prefix:key1', 'prefix:key2'],
            ],
            ['SUNION',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            ['SUNIONSTORE',
                ['key:destination', 'key1', 'key2'],
                ['prefix:key:destination', 'prefix:key1', 'prefix:key2'],
            ],
            ['SDIFF',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            ['SDIFFSTORE',
                ['key:destination', 'key1', 'key2'],
                ['prefix:key:destination', 'prefix:key1', 'prefix:key2'],
            ],
            ['SMEMBERS',
                ['key'],
                ['prefix:key'],
            ],
            ['SMISMEMBER',
                ['key', 'member1', 'member2', 'member3'],
                ['prefix:key', 'member1', 'member2', 'member3'],
            ],
            ['SRANDMEMBER',
                ['key', 1],
                ['prefix:key', 1],
            ],
            ['ZADD',
                ['key', 'score1', 'member1', 'score2', 'member2'],
                ['prefix:key', 'score1', 'member1', 'score2', 'member2'],
            ],
            ['ZINCRBY',
                ['key', 1.0, 'member'],
                ['prefix:key', 1.0, 'member'],
            ],
            ['ZREM',
                ['key', 'member1', 'member2', 'member3'],
                ['prefix:key', 'member1', 'member2', 'member3'],
            ],
            ['ZRANGE',
                ['key', 0, 100, 'WITHSCORES'],
                ['prefix:key', 0, 100, 'WITHSCORES'],
            ],
            ['ZREVRANGE',
                ['key', 0, 100, 'WITHSCORES'],
                ['prefix:key', 0, 100, 'WITHSCORES'],
            ],
            ['ZRANGEBYSCORE',
                ['key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'],
                ['prefix:key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'],
            ],
            ['ZCARD',
                ['key'],
                ['prefix:key'],
            ],
            ['ZSCORE',
                ['key', 'member'],
                ['prefix:key', 'member'],
            ],
            ['ZREMRANGEBYSCORE',
                ['key', 0, 10],
                ['prefix:key', 0, 10],
            ],
            /* ---------------- Redis 2.0 ---------------- */
            ['SETEX',
                ['key', 10, 'value'],
                ['prefix:key', 10, 'value'],
            ],
            ['APPEND',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['SUBSTR',
                ['key', 5, 10],
                ['prefix:key', 5, 10],
            ],
            ['BLPOP',
                ['key1', 'key2', 'key3', 10],
                ['prefix:key1', 'prefix:key2', 'prefix:key3', 10],
            ],
            ['BRPOP',
                ['key1', 'key2', 'key3', 10],
                ['prefix:key1', 'prefix:key2', 'prefix:key3', 10],
            ],
            ['ZUNIONSTORE',
                ['key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'],
                ['prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'],
            ],
            ['ZINTERSTORE',
                ['key:destination', 2, 'key1', 'key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'],
                ['prefix:key:destination', 2, 'prefix:key1', 'prefix:key2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum'],
            ],
            ['ZCOUNT',
                ['key', 0, 10],
                ['prefix:key', 0, 10],
            ],
            ['ZRANK',
                ['key', 'member'],
                ['prefix:key', 'member'],
            ],
            ['ZREVRANK',
                ['key', 'member'],
                ['prefix:key', 'member'],
            ],
            ['ZREMRANGEBYRANK',
                ['key', 0, 10],
                ['prefix:key', 0, 10],
            ],
            ['HSET',
                ['key', 'field', 'value'],
                ['prefix:key', 'field', 'value'],
            ],
            ['HSETNX',
                ['key', 'field', 'value'],
                ['prefix:key', 'field', 'value'],
            ],
            ['HMSET',
                ['key', 'field1', 'value1', 'field2', 'value2'],
                ['prefix:key', 'field1', 'value1', 'field2', 'value2'],
            ],
            ['HINCRBY',
                ['key', 'field', 10],
                ['prefix:key', 'field', 10],
            ],
            ['HGET',
                ['key', 'field'],
                ['prefix:key', 'field'],
            ],
            ['HMGET',
                ['key', 'field1', 'field2', 'field3'],
                ['prefix:key', 'field1', 'field2', 'field3'],
            ],
            ['HDEL',
                ['key', 'field1', 'field2', 'field3'],
                ['prefix:key', 'field1', 'field2', 'field3'],
            ],
            ['HEXISTS',
                ['key', 'field'],
                ['prefix:key', 'field'],
            ],
            ['HLEN',
                ['key'],
                ['prefix:key'],
            ],
            ['HKEYS',
                ['key'],
                ['prefix:key'],
            ],
            ['HVALS',
                ['key'],
                ['prefix:key'],
            ],
            ['HGETALL',
                ['key'],
                ['prefix:key'],
            ],
            ['SUBSCRIBE',
                ['channel:foo', 'channel:hoge'],
                ['prefix:channel:foo', 'prefix:channel:hoge'],
            ],
            ['UNSUBSCRIBE',
                ['channel:foo', 'channel:hoge'],
                ['prefix:channel:foo', 'prefix:channel:hoge'],
            ],
            ['PSUBSCRIBE',
                ['channel:foo:*', 'channel:hoge:*'],
                ['prefix:channel:foo:*', 'prefix:channel:hoge:*'],
            ],
            ['PUNSUBSCRIBE',
                ['channel:foo:*', 'channel:hoge:*'],
                ['prefix:channel:foo:*', 'prefix:channel:hoge:*'],
            ],
            ['PUBLISH',
                ['channel', 'message'],
                ['prefix:channel', 'message'],
            ],
            /* ---------------- Redis 2.2 ---------------- */
            ['PERSIST',
                ['key'],
                ['prefix:key'],
            ],
            ['STRLEN',
                ['key'],
                ['prefix:key'],
            ],
            ['SETRANGE',
                ['key', 5, 'string'],
                ['prefix:key', 5, 'string'],
            ],
            ['GETRANGE',
                ['key', 5, 10],
                ['prefix:key', 5, 10],
            ],
            ['SETBIT',
                ['key', 7, 1],
                ['prefix:key', 7, 1],
            ],
            ['GETBIT',
                ['key', 100],
                ['prefix:key', 100],
            ],
            ['RPUSHX',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['LPUSHX',
                ['key', 'value'],
                ['prefix:key', 'value'],
            ],
            ['LINSERT',
                ['key', 'before', 'value1', 'value2'],
                ['prefix:key', 'before', 'value1', 'value2'],
            ],
            ['BRPOPLPUSH',
                ['key:source', 'key:destination', 10],
                ['prefix:key:source', 'prefix:key:destination', 10],
            ],
            ['ZREVRANGEBYSCORE',
                ['key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'],
                ['prefix:key', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'],
            ],
            ['WATCH',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            /* ---------------- Redis 2.6 ---------------- */
            ['PTTL',
                ['key', 10],
                ['prefix:key', 10],
            ],
            ['PEXPIRE',
                ['key', 1500],
                ['prefix:key', 1500],
            ],
            ['PEXPIREAT',
                ['key', 1555555555005],
                ['prefix:key', 1555555555005],
            ],
            ['PSETEX',
                ['key', 1500, 'value'],
                ['prefix:key', 1500, 'value'],
            ],
            ['INCRBYFLOAT',
                ['key', 10.5],
                ['prefix:key', 10.5],
            ],
            ['BITOP',
                ['AND', 'key:dst', 'key:01', 'key:02'],
                ['AND', 'prefix:key:dst', 'prefix:key:01', 'prefix:key:02'],
            ],
            ['BITCOUNT',
                ['key', 0, 10],
                ['prefix:key', 0, 10],
            ],
            ['HINCRBYFLOAT',
                ['key', 'field', 10.5],
                ['prefix:key', 'field', 10.5],
            ],
            ['EVAL',
                ['return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'foo', 'hoge', 'bar', 'piyo'],
                ['return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo'],
            ],
            ['EVALSHA',
                ['a42059b356c875f0717db19a51f6aaca9ae659ea', 2, 'foo', 'hoge', 'bar', 'piyo'],
                ['a42059b356c875f0717db19a51f6aaca9ae659ea', 2, 'prefix:foo', 'prefix:hoge', 'bar', 'piyo'],
            ],
            ['BITPOS',
                ['key', 0],
                ['prefix:key', 0],
            ],
            ['MIGRATE',
                ['127.0.0.1', '6379', 'key', '0', '10'],
                ['127.0.0.1', '6379', 'prefix:key', '0', '10'],
            ],
            /* ---------------- Redis 2.8 ---------------- */
            ['SSCAN',
                ['key', '0', 'MATCH', 'member:*', 'COUNT', 10],
                ['prefix:key', '0', 'MATCH', 'member:*', 'COUNT', 10],
            ],
            ['ZSCAN',
                ['key', '0', 'MATCH', 'member:*', 'COUNT', 10],
                ['prefix:key', '0', 'MATCH', 'member:*', 'COUNT', 10],
            ],
            ['HSCAN',
                ['key', '0', 'MATCH', 'field:*', 'COUNT', 10],
                ['prefix:key', '0', 'MATCH', 'field:*', 'COUNT', 10],
            ],
            ['PFADD',
                ['key', 'a', 'b', 'c'],
                ['prefix:key', 'a', 'b', 'c'],
            ],
            ['PFCOUNT',
                ['key:1', 'key:2', 'key:3'],
                ['prefix:key:1', 'prefix:key:2', 'prefix:key:3'],
            ],
            ['PFMERGE',
                ['key:1', 'key:2', 'key:3'],
                ['prefix:key:1', 'prefix:key:2', 'prefix:key:3'],
            ],
            ['ZLEXCOUNT',
                ['key', '-', '+'],
                ['prefix:key', '-', '+'],
            ],
            ['ZRANGEBYLEX',
                ['key', '-', '+', 'LIMIT', '0', '10'],
                ['prefix:key', '-', '+', 'LIMIT', '0', '10'],
            ],
            ['ZREMRANGEBYLEX',
                ['key', '-', '+'],
                ['prefix:key', '-', '+'],
            ],
            ['ZREVRANGEBYLEX',
                ['key', '+', '-', 'LIMIT', '0', '10'],
                ['prefix:key', '+', '-', 'LIMIT', '0', '10'],
            ],
            /* ---------------- Redis 3.0 ---------------- */
            ['MIGRATE',
                ['127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'],
                ['127.0.0.1', '6379', 'prefix:key', '0', '10', 'COPY', 'REPLACE'],
            ],
            ['EXISTS',
                ['key1', 'key2', 'key3'],
                ['prefix:key1', 'prefix:key2', 'prefix:key3'],
            ],
            /* ---------------- Redis 3.2 ---------------- */
            ['HSTRLEN',
                ['key', 'field'],
                ['prefix:key', 'field'],
            ],
            ['BITFIELD',
                ['key', 'GET', 'u8', '0', 'SET', 'u8', '0', '1'],
                ['prefix:key', 'GET', 'u8', '0', 'SET', 'u8', '0', '1'],
            ],
            ['GEOADD',
                ['key', '13.361389', '38.115556', 'member:1', '15.087269', '37.502669', 'member:2'],
                ['prefix:key', '13.361389', '38.115556', 'member:1', '15.087269', '37.502669', 'member:2'],
            ],
            ['GEOHASH',
                ['key', 'member:1', 'member:2'],
                ['prefix:key', 'member:1', 'member:2'],
            ],
            ['GEOPOS',
                ['key', 'member:1', 'member:2'],
                ['prefix:key', 'member:1', 'member:2'],
            ],
            ['GEODIST',
                ['key', 'member:1', 'member:2', 'km'],
                ['prefix:key', 'member:1', 'member:2', 'km'],
            ],
            ['GEORADIUS',
                ['key', '15', '37', '200', 'km'],
                ['prefix:key', '15', '37', '200', 'km'],
            ],
            ['GEORADIUS',
                ['key', '15', '37', '200', 'km', 'WITHDIST', 'STORE', 'key:store', 'STOREDIST', 'key:storedist'],
                ['prefix:key', '15', '37', '200', 'km', 'WITHDIST', 'STORE', 'prefix:key:store', 'STOREDIST', 'prefix:key:storedist'],
            ],
            ['GEORADIUSBYMEMBER',
                ['key', 'member', '100', 'km'],
                ['prefix:key', 'member', '100', 'km'],
            ],
            ['GEORADIUSBYMEMBER',
                ['key', 'member', '100', 'km', 'WITHDIST', 'STORE', 'key:store', 'STOREDIST', 'key:storedist'],
                ['prefix:key', 'member', '100', 'km', 'WITHDIST', 'STORE', 'prefix:key:store', 'STOREDIST', 'prefix:key:storedist'],
            ],
            /* ---------------- Redis 5.0 ---------------- */
            ['XADD',
                ['key', '*', ['field' => 'value']],
                ['prefix:key', '*', ['field' => 'value']],
            ],
            ['XRANGE',
                ['key', '-', '+'],
                ['prefix:key', '-', '+'],
            ],
            ['XREVRANGE',
                ['key', '+', '-'],
                ['prefix:key', '+', '-'],
            ],
            ['XDEL',
                ['key', 'id'],
                ['prefix:key', 'id'],
            ],
            ['XLEN',
                ['key'],
                ['prefix:key'],
            ],
            ['XACK',
                ['key', 'group', 'id'],
                ['prefix:key', 'group', 'id'],
            ],
            ['XTRIM',
                ['key', 'MAXLEN', 100],
                ['prefix:key', 'MAXLEN', 100],
            ],
            /* ---------------- Redis 6.2 ---------------- */
            ['GETDEL',
                ['key'],
                ['prefix:key'],
            ],
            ['LMOVE',
                ['key:source', 'key:destination', 'left', 'right'],
                ['prefix:key:source', 'prefix:key:destination', 'left', 'right'],
            ],
            ['BLMOVE',
                ['key:source', 'key:destination', 'left', 'right', 10],
                ['prefix:key:source', 'prefix:key:destination', 'left', 'right', 10],
            ],
            /* ---------------- Redis 7.0 ---------------- */
            ['EXPIRETIME',
                ['key'],
                ['prefix:key'],
            ],
        ];
    }
}
