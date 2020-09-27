<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\Processor\ProcessorChain;
use Predis\Command\Processor\ProcessorInterface;
use PredisTestCase;

/**
 *
 */
class RedisFactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportedCommands(): void
    {
        $factory = new RedisFactory();

        foreach ($this->getExpectedCommands() as $commandID) {
            $this->assertTrue($factory->supports($commandID), "Command factory does not support $commandID");
        }
    }

    /**
     * @group disconnected
     */
    public function testSupportCommand(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('info'));
        $this->assertTrue($factory->supports('INFO'));

        $this->assertFalse($factory->supports('unknown'));
        $this->assertFalse($factory->supports('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testSupportCommands(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('get', 'set'));
        $this->assertTrue($factory->supports('GET', 'SET'));

        $this->assertFalse($factory->supports('get', 'unknown'));

        $this->assertFalse($factory->supports('unknown1', 'unknown2'));
    }

    /**
     * @group disconnected
     */
    public function testGetCommandClass(): void
    {
        $factory = new RedisFactory();

        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('ping'));
        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('PING'));

        $this->assertNull($factory->getCommandClass('unknown'));
        $this->assertNull($factory->getCommandClass('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testDefineCommand(): void
    {
        $factory = new RedisFactory();

        $command = $this->getMockBuilder('Predis\Command\CommandInterface')
            ->getMock();

        $factory->define('mock', get_class($command));

        $this->assertTrue($factory->supports('mock'));
        $this->assertTrue($factory->supports('MOCK'));

        $this->assertSame(get_class($command), $factory->getCommandClass('mock'));
    }

    /**
     * @group disconnected
     */
    public function testUndefineCommandInClassAutoload(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('PING'));
        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('PING'));

        $factory->undefine('PING');

        $this->assertFalse($factory->supports('PING'));
        $this->assertNull($factory->getCommandClass('PING'));
    }

    /**
     * @group disconnected
     */
    public function testUndefineCommandInClassMap(): void
    {
        $factory = new RedisFactory();

        $commandClass = get_class($this->getMockBuilder('Predis\Command\CommandInterface')->getMock());
        $factory->define('MOCK', $commandClass);

        $this->assertTrue($factory->supports('MOCK'));
        $this->assertSame($commandClass, $factory->getCommandClass('MOCK'));

        $factory->undefine('MOCK');

        $this->assertFalse($factory->supports('MOCK'));
        $this->assertNull($factory->getCommandClass('MOCK'));
    }

    /**
     * @group disconnected
     */
    public function testDefineInvalidCommand(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Class stdClass must implement Predis\Command\CommandInterface");

        $factory = new RedisFactory();

        $factory->define('mock', 'stdClass');
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments(): void
    {
        $factory = new RedisFactory();

        $command = $factory->create('info');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('INFO', $command->getId());
        $this->assertEquals(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithArguments(): void
    {
        $factory = new RedisFactory();

        $arguments = array('foo', 'bar');
        $command = $factory->create('set', $arguments);

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateUndefinedCommand(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage("Command `UNKNOWN` is not a registered Redis command.");

        $factory = new RedisFactory();

        $factory->create('unknown');
    }

    /**
     * @group disconnected
     */
    public function testGetDefaultProcessor(): void
    {
        $factory = new RedisFactory();

        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetProcessor(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();

        $factory = new RedisFactory();
        $factory->setProcessor($processor);

        $this->assertSame($processor, $factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetAndUnsetProcessor(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();

        $factory = new RedisFactory();

        $factory->setProcessor($processor);
        $this->assertSame($processor, $factory->getProcessor());

        $factory->setProcessor(null);
        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSingleProcessor(): void
    {
        // Could it be that objects passed to the return callback of a mocked
        // method are cloned instead of being passed by reference?
        $argsRef = null;

        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
            ->willReturnCallback(
                function (CommandInterface $cmd) use (&$argsRef) {
                    $cmd->setRawArguments($argsRef = array_map('strtoupper', $cmd->getArguments()));
                }
            );

        $factory = new RedisFactory();
        $factory->setProcessor($processor);
        $factory->create('set', array('foo', 'bar'));

        $this->assertSame(array('FOO', 'BAR'), $argsRef);
    }

    /**
     * @group disconnected
     */
    public function testChainOfProcessors(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();
        $processor
            ->expects($this->exactly(2))
            ->method('process');

        $chain = new ProcessorChain();
        $chain->add($processor);
        $chain->add($processor);

        $factory = new RedisFactory();
        $factory->setProcessor($chain);

        $factory->create('info');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns the expected list of commands supported by the tested factory.
     *
     * @return array List of supported commands.
     */
    protected function getExpectedCommands(): array
    {
        return array(
            'EXISTS',
            'DEL',
            'TYPE',
            'KEYS',
            'RANDOMKEY',
            'RENAME',
            'RENAMENX',
            'EXPIRE',
            'EXPIREAT',
            'TTL',
            'MOVE',
            'SORT',
            'DUMP',
            'RESTORE',
            'UNLINK',
            'SET',
            'SETNX',
            'MSET',
            'MSETNX',
            'GET',
            'MGET',
            'GETSET',
            'INCR',
            'INCRBY',
            'DECR',
            'DECRBY',
            'RPUSH',
            'LPUSH',
            'LLEN',
            'LRANGE',
            'LTRIM',
            'LINDEX',
            'LSET',
            'LREM',
            'LPOP',
            'RPOP',
            'RPOPLPUSH',
            'SADD',
            'SREM',
            'SPOP',
            'SMOVE',
            'SCARD',
            'SISMEMBER',
            'SINTER',
            'SINTERSTORE',
            'SUNION',
            'SUNIONSTORE',
            'SDIFF',
            'SDIFFSTORE',
            'SMEMBERS',
            'SRANDMEMBER',
            'ZADD',
            'ZINCRBY',
            'ZREM',
            'ZRANGE',
            'ZREVRANGE',
            'ZRANGEBYSCORE',
            'ZCARD',
            'ZSCORE',
            'ZREMRANGEBYSCORE',
            'PING',
            'AUTH',
            'SELECT',
            'ECHO',
            'QUIT',
            'INFO',
            'SLAVEOF',
            'MONITOR',
            'DBSIZE',
            'FLUSHDB',
            'FLUSHALL',
            'SAVE',
            'BGSAVE',
            'LASTSAVE',
            'SHUTDOWN',
            'BGREWRITEAOF',
            'SETEX',
            'APPEND',
            'SUBSTR',
            'BLPOP',
            'BRPOP',
            'ZUNIONSTORE',
            'ZINTERSTORE',
            'ZCOUNT',
            'ZRANK',
            'ZREVRANK',
            'ZREMRANGEBYRANK',
            'HSET',
            'HSETNX',
            'HMSET',
            'HINCRBY',
            'HGET',
            'HMGET',
            'HDEL',
            'HEXISTS',
            'HLEN',
            'HKEYS',
            'HVALS',
            'HGETALL',
            'MULTI',
            'EXEC',
            'DISCARD',
            'SUBSCRIBE',
            'UNSUBSCRIBE',
            'PSUBSCRIBE',
            'PUNSUBSCRIBE',
            'PUBLISH',
            'CONFIG',
            'PERSIST',
            'STRLEN',
            'SETRANGE',
            'GETRANGE',
            'SETBIT',
            'GETBIT',
            'RPUSHX',
            'LPUSHX',
            'LINSERT',
            'BRPOPLPUSH',
            'ZREVRANGEBYSCORE',
            'WATCH',
            'UNWATCH',
            'OBJECT',
            'SLOWLOG',
            'CLIENT',
            'PTTL',
            'PEXPIRE',
            'PEXPIREAT',
            'MIGRATE',
            'PSETEX',
            'INCRBYFLOAT',
            'BITOP',
            'BITCOUNT',
            'HINCRBYFLOAT',
            'EVAL',
            'EVALSHA',
            'SCRIPT',
            'TIME',
            'SENTINEL',
            'SCAN',
            'BITPOS',
            'SSCAN',
            'ZSCAN',
            'ZLEXCOUNT',
            'ZRANGEBYLEX',
            'ZREMRANGEBYLEX',
            'ZREVRANGEBYLEX',
            'HSCAN',
            'PUBSUB',
            'PFADD',
            'PFCOUNT',
            'PFMERGE',
            'COMMAND',
            'HSTRLEN',
            'BITFIELD',
            'GEOADD',
            'GEOHASH',
            'GEOPOS',
            'GEODIST',
            'GEORADIUS',
            'GEORADIUSBYMEMBER',
        );
    }
}
