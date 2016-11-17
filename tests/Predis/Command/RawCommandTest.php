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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The arguments array must contain at least the command ID.
     */
    public function testExceptionOnMissingCommandID()
    {
        new RawCommand(array());
    }

    /**
     * The signature of RawCommand::create() requires one argument which is the
     * ID of the command (other arguments are fetched dinamically). If the first
     * argument is missing, PHP emits an E_WARNING.
     *
     * @group disconnected
     */
    public function testPHPWarningOnMissingCommandIDWithStaticCreate()
    {
        if (version_compare(PHP_VERSION, "7.1", '>')) {
            $this->markTestSkipped('only for PHP < 7.1');
        }
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        RawCommand::create();
    }

    /**
     * The signature of RawCommand::create() requires one argument which is the
     * ID of the command (other arguments are fetched dinamically). If the first
     * argument is missing, PHP 7.1 throw an exception
     *
     * @group disconnected
     */
    public function testPHPWarningOnMissingCommandIDWithStaticCreate71()
    {
        if (version_compare(PHP_VERSION, "7.1", '<')) {
            $this->markTestSkipped('only for PHP > 7.1');
        }
        $this->setExpectedException('ArgumentCountError');
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
        $slot = 1024;
        $arguments = array('SET', 'key', 'value');
        $command = new RawCommand($arguments);

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
        $command = new RawCommand(array('set', 'key', 'value'));

        $this->assertSame('SET', $command->getId());
    }
}
