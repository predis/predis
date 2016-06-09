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

use PredisTestCase;

/**
 * @group realm-scripting
 */
class ScriptCommandTest extends PredisTestCase
{
    const LUA_SCRIPT = 'return { KEYS[1], KEYS[2], ARGV[1], ARGV[2] }';
    const LUA_SCRIPT_SHA1 = '6e07f61f502e36d123fe28523076af588f5c315e';

    /**
     * @group disconnected
     */
    public function testGetId()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript'));

        $this->assertSame('EVALSHA', $command->getId());
    }

    /**
     * @group disconnected
     */
    public function testGetScriptHash()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->will($this->returnValue(2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(self::LUA_SCRIPT_SHA1, $command->getScriptHash());
    }

    /**
     * @group disconnected
     */
    public function testGetKeys()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->will($this->returnValue(2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array('key1', 'key2'), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetKeysWithZeroKeysCount()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));

        $command->setArguments($arguments = array('value1', 'value2', 'value3'));

        $this->assertSame(array(), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetKeysWithNegativeKeysCount()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->will($this->returnValue(-2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array('key1', 'key2'), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetArguments()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->will($this->returnValue(2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 2), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentsWithZeroKeysCount()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 0), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentsWithNegativeKeysCount()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->will($this->returnValue(-2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 2), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetEvalArguments()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->will($this->returnValue(2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT, 2), $arguments), $command->getEvalArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetEvalCommand()
    {
        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->will($this->returnValue(self::LUA_SCRIPT));
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->will($this->returnValue(2));

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $evalCMD = new RawCommand('EVAL', array_merge(array(self::LUA_SCRIPT, 2), $arguments));

        $this->assertRedisCommand($evalCMD, $command->getEvalCommand());
    }
}
