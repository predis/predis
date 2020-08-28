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
use PHPUnit\Framework\MockObject\MockObject;

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
    public function testGetId(): void
    {
        /** @var CommandInterface */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript'))
            ->getMock();

        $this->assertSame('EVALSHA', $command->getId());
    }

    /**
     * @group disconnected
     */
    public function testGetScriptHash(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->willReturn(2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(self::LUA_SCRIPT_SHA1, $command->getScriptHash());
    }

    /**
     * @group disconnected
     */
    public function testGetKeys(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->willReturn(2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array('key1', 'key2'), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetKeysWithZeroKeysCount(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);

        $command->setArguments($arguments = array('value1', 'value2', 'value3'));

        $this->assertSame(array(), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetKeysWithNegativeKeysCount(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->willReturn(-2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array('key1', 'key2'), $command->getKeys());
    }

    /**
     * @group disconnected
     */
    public function testGetArguments(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->willReturn(2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 2), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentsWithZeroKeysCount(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 0), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentsWithNegativeKeysCount(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->willReturn(-2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT_SHA1, 2), $arguments), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetEvalArguments(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->willReturn(2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $this->assertSame(array_merge(array(self::LUA_SCRIPT, 2), $arguments), $command->getEvalArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetEvalCommand(): void
    {
        /** @var ScriptCommand|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->exactly(2))
            ->method('getScript')
            ->willReturn(self::LUA_SCRIPT);
        $command
            ->expects($this->once())
            ->method('getKeysCount')
            ->willReturn(2);

        $command->setArguments($arguments = array('key1', 'key2', 'value1', 'value2'));

        $evalCMD = new RawCommand('EVAL', array_merge(array(self::LUA_SCRIPT, 2), $arguments));

        $this->assertRedisCommand($evalCMD, $command->getEvalCommand());
    }
}
