<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Hash;

use \PHPUnit_Framework_TestCase as StandardTestCase;
use Predis\Profile\ServerProfile;
use Predis\Distribution\HashRing;

/**
 *
 */
class CommandHashStrategyTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportsKeyTags()
    {
        $expected = -1938594527;
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();

        $this->assertSame($expected, $hashstrategy->getKeyHash($distribution, '{foo}'));
        $this->assertSame($expected, $hashstrategy->getKeyHash($distribution, '{foo}:bar'));
        $this->assertSame($expected, $hashstrategy->getKeyHash($distribution, '{foo}:baz'));
        $this->assertSame($expected, $hashstrategy->getKeyHash($distribution, 'bar:{foo}:bar'));

        $this->assertSame(0, $hashstrategy->getKeyHash($distribution, ''));
        $this->assertSame(0, $hashstrategy->getKeyHash($distribution, '{}'));
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $hashstrategy = new CommandHashStrategy();

        $this->assertSame($this->getExpectedCommands(), $hashstrategy->getSupportedCommands());
    }

    /**
     * @group disconnected
     */
    public function testReturnsNullOnUnsupportedCommand()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $command = ServerProfile::getDevelopment()->createCommand('ping');

        $this->assertNull($hashstrategy->getHash($distribution, $command));
    }

    /**
     * @group disconnected
     */
    public function testFirstKeyCommands()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('key');

        foreach ($this->getExpectedCommands('keys-first') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommands()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('{key}:1', '{key}:2', '{key}:3', '{key}:4');

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommands()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('{key}:1', 'value1', '{key}:2', 'value2');

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommands()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('{key}:1', '{key}:2', 10);

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForZsetAggregationCommands()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('{key}:destination', 2, '{key}:1', '{key}:1', array('aggregate' => 'SUM'));

        foreach ($this->getExpectedCommands('keys-zaggregated') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForBitOpCommand()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();
        $arguments = array('AND', '{key}:destination', '{key}:src:1', '{key}:src:2');

        foreach ($this->getExpectedCommands('keys-bitop') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($hashstrategy->getHash($distribution, $command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testUnsettingCommandHandler()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();

        $hashstrategy->setCommandHandler('set');
        $hashstrategy->setCommandHandler('get', null);

        $command = $profile->createCommand('set', array('key', 'value'));
        $this->assertNull($hashstrategy->getHash($distribution, $command));

        $command = $profile->createCommand('get', array('key'));
        $this->assertNull($hashstrategy->getHash($distribution, $command));
    }

    /**
     * @group disconnected
     */
    public function testSettingCustomCommandHandler()
    {
        $distribution = new HashRing();
        $hashstrategy = new CommandHashStrategy();
        $profile = ServerProfile::getDevelopment();

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
                 ->will($this->returnValue('key'));

        $hashstrategy->setCommandHandler('get', $callable);

        $command = $profile->createCommand('get', array('key'));
        $this->assertNotNull($hashstrategy->getHash($distribution, $command));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns the list of expected supported commands.
     *
     * @param string $type Optional type of command (based on its keys)
     * @return array
     */
    protected function getExpectedCommands($type = null)
    {
        $commands = array(
            /* commands operating on the key space */
            'EXISTS'                => 'keys-first',
            'DEL'                   => 'keys-all',
            'TYPE'                  => 'keys-first',
            'EXPIRE'                => 'keys-first',
            'EXPIREAT'              => 'keys-first',
            'PERSIST'               => 'keys-first',
            'PEXPIRE'               => 'keys-first',
            'PEXPIREAT'             => 'keys-first',
            'TTL'                   => 'keys-first',
            'PTTL'                  => 'keys-first',
            'SORT'                  => 'keys-first', // TODO

            /* commands operating on string values */
            'APPEND'                => 'keys-first',
            'DECR'                  => 'keys-first',
            'DECRBY'                => 'keys-first',
            'GET'                   => 'keys-first',
            'GETBIT'                => 'keys-first',
            'MGET'                  => 'keys-all',
            'SET'                   => 'keys-first',
            'GETRANGE'              => 'keys-first',
            'GETSET'                => 'keys-first',
            'INCR'                  => 'keys-first',
            'INCRBY'                => 'keys-first',
            'SETBIT'                => 'keys-first',
            'SETEX'                 => 'keys-first',
            'MSET'                  => 'keys-interleaved',
            'MSETNX'                => 'keys-interleaved',
            'SETNX'                 => 'keys-first',
            'SETRANGE'              => 'keys-first',
            'STRLEN'                => 'keys-first',
            'SUBSTR'                => 'keys-first',
            'BITOP'                 => 'keys-bitop',
            'BITCOUNT'              => 'keys-first',

            /* commands operating on lists */
            'LINSERT'               => 'keys-first',
            'LINDEX'                => 'keys-first',
            'LLEN'                  => 'keys-first',
            'LPOP'                  => 'keys-first',
            'RPOP'                  => 'keys-first',
            'RPOPLPUSH'             => 'keys-all',
            'BLPOP'                 => 'keys-blockinglist',
            'BRPOP'                 => 'keys-blockinglist',
            'BRPOPLPUSH'            => 'keys-blockinglist',
            'LPUSH'                 => 'keys-first',
            'LPUSHX'                => 'keys-first',
            'RPUSH'                 => 'keys-first',
            'RPUSHX'                => 'keys-first',
            'LRANGE'                => 'keys-first',
            'LREM'                  => 'keys-first',
            'LSET'                  => 'keys-first',
            'LTRIM'                 => 'keys-first',

            /* commands operating on sets */
            'SADD'                  => 'keys-first',
            'SCARD'                 => 'keys-first',
            'SDIFF'                 => 'keys-all',
            'SDIFFSTORE'            => 'keys-all',
            'SINTER'                => 'keys-all',
            'SINTERSTORE'           => 'keys-all',
            'SUNION'                => 'keys-all',
            'SUNIONSTORE'           => 'keys-all',
            'SISMEMBER'             => 'keys-first',
            'SMEMBERS'              => 'keys-first',
            'SPOP'                  => 'keys-first',
            'SRANDMEMBER'           => 'keys-first',
            'SREM'                  => 'keys-first',

            /* commands operating on sorted sets */
            'ZADD'                  => 'keys-first',
            'ZCARD'                 => 'keys-first',
            'ZCOUNT'                => 'keys-first',
            'ZINCRBY'               => 'keys-first',
            'ZINTERSTORE'           => 'keys-zaggregated',
            'ZRANGE'                => 'keys-first',
            'ZRANGEBYSCORE'         => 'keys-first',
            'ZRANK'                 => 'keys-first',
            'ZREM'                  => 'keys-first',
            'ZREMRANGEBYRANK'       => 'keys-first',
            'ZREMRANGEBYSCORE'      => 'keys-first',
            'ZREVRANGE'             => 'keys-first',
            'ZREVRANGEBYSCORE'      => 'keys-first',
            'ZREVRANK'              => 'keys-first',
            'ZSCORE'                => 'keys-first',
            'ZUNIONSTORE'           => 'keys-zaggregated',

            /* commands operating on hashes */
            'HDEL'                  => 'keys-first',
            'HEXISTS'               => 'keys-first',
            'HGET'                  => 'keys-first',
            'HGETALL'               => 'keys-first',
            'HMGET'                 => 'keys-first',
            'HINCRBY'               => 'keys-first',
            'HINCRBYFLOAT'          => 'keys-first',
            'HKEYS'                 => 'keys-first',
            'HLEN'                  => 'keys-first',
            'HSET'                  => 'keys-first',
            'HSETNX'                => 'keys-first',
            'HVALS'                 => 'keys-first',
        );

        if (isset($type)) {
            $commands = array_filter($commands, function($expectedType) use($type) {
                return $expectedType === $type;
            });
        }

        return array_keys($commands);
    }
}
