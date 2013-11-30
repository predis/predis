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

use PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class RawCommandTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithCommandID()
    {
        $commandID = 'PING';
        $command = new RawCommand(array($commandID));

        $this->assertSame($commandID, $command->getId());
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCommandIDAndArguments()
    {
        $commandID = 'SET';
        $commandArgs = array('foo', 'bar');

        $command = new RawCommand(array_merge((array) $commandID, $commandArgs));

        $this->assertSame($commandID, $command->getId());
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testStaticCreate()
    {
        $command = RawCommand::create('SET');
        $this->assertSame('SET', $command->getId());
        $this->assertEmpty($command->getArguments());

        $command = RawCommand::create('SET', 'foo', 'bar');
        $this->assertSame('SET', $command->getId());
        $this->assertSame(array('foo', 'bar'), $command->getArguments());
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Arguments array is missing the command ID
     */
    public function testExceptionOnMissingCommandID()
    {
        $command = new RawCommand(array());
    }

    /**
     * @group disconnected
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Missing argument 1 for Predis\Command\RawCommand::create()
     */
    public function testPHPWarningOnMissingCommandIDWithStaticCreate()
    {
        RawCommand::create();
    }

    /**
     * @group disconnected
     */
    public function testSetArguments()
    {
        $commandID = 'SET';
        $command = new RawCommand(array($commandID));

        $command->setArguments($commandArgs = array('foo', 'bar'));
        $this->assertSame($commandArgs, $command->getArguments());

        $command->setArguments($commandArgs = array('hoge', 'piyo'));
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments()
    {
        $commandID = 'SET';
        $command = new RawCommand(array($commandID));

        $command->setRawArguments($commandArgs = array('foo', 'bar'));
        $this->assertSame($commandArgs, $command->getArguments());

        $command->setRawArguments($commandArgs = array('hoge', 'piyo'));
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetHash()
    {
        $hash = "key-hash";
        $arguments = array('SET', 'key', 'value');
        $command = new RawCommand($arguments);

        $this->assertNull($command->getHash());

        $command->setHash($hash);
        $this->assertSame($hash, $command->getHash());

        $command->setArguments(array('hoge', 'piyo'));
        $this->assertNull($command->getHash());
    }

    /**
     * @group disconnected
     */
    public function testToString()
    {
        $arguments = array('SET', 'key', 'value');
        $expected = implode(' ', $arguments);

        $command = new RawCommand($arguments);

        $this->assertEquals($expected, (string) $command);
    }
}
