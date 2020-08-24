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
 *
 */
class RawCommandTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithCommandID()
    {
        $commandID = 'PING';
        $command = new RawCommand($commandID);

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

        $command = new RawCommand($commandID, $commandArgs);

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
     * The signature of RawCommand::create() requires one argument which is the
     * ID of the command (other arguments are fetched dinamically). If the first
     * argument is missing a standard PHP exception is thrown on PHP >= 7.1.
     *
     * @group disconnected
     */
    public function testPHPExceptionOnMissingCommandIDWithStaticCreate()
    {
        $this->expectException('ArgumentCountError');

        RawCommand::create();
    }

    /**
     * @group disconnected
     */
    public function testSetArguments()
    {
        $commandID = 'SET';
        $command = new RawCommand($commandID);

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
        $command = new RawCommand($commandID);

        $command->setRawArguments($commandArgs = array('foo', 'bar'));
        $this->assertSame($commandArgs, $command->getArguments());

        $command->setRawArguments($commandArgs = array('hoge', 'piyo'));
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex()
    {
        $command = new RawCommand('GET', array('key'));

        $this->assertSame('key', $command->getArgument(0));
        $this->assertNull($command->getArgument(1));
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetHash()
    {
        $slot = 1024;
        $arguments = array('key', 'value');
        $command = new RawCommand('SET', $arguments);

        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $this->assertSame($slot, $command->getSlot());

        $command->setArguments(array('hoge', 'piyo'));
        $this->assertNull($command->getSlot());
    }

    /**
     * @group disconnected
     */
    public function testNormalizesCommandIdentifiersToUppercase()
    {
        $command = new RawCommand('set', array('key', 'value'));

        $this->assertSame('SET', $command->getId());
    }
}
