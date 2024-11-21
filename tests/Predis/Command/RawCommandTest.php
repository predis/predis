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

namespace Predis\Command;

use PredisTestCase;

class RawCommandTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithCommandID(): void
    {
        $commandID = 'PING';
        $command = new RawCommand($commandID);

        $this->assertSame($commandID, $command->getId());
        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCommandIDAndArguments(): void
    {
        $commandID = 'SET';
        $commandArgs = ['foo', 'bar'];

        $command = new RawCommand($commandID, $commandArgs);

        $this->assertSame($commandID, $command->getId());
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testStaticCreate(): void
    {
        $command = RawCommand::create('SET');
        $this->assertSame('SET', $command->getId());
        $this->assertEmpty($command->getArguments());

        $command = RawCommand::create('SET', 'foo', 'bar');
        $this->assertSame('SET', $command->getId());
        $this->assertSame(['foo', 'bar'], $command->getArguments());
    }

    /**
     * The signature of RawCommand::create() requires one argument which is the
     * ID of the command (other arguments are fetched dynamically). If the first
     * argument is missing a standard PHP exception is thrown on PHP >= 7.1.
     *
     * @group disconnected
     */
    public function testPHPExceptionOnMissingCommandIDWithStaticCreate(): void
    {
        $this->expectException('ArgumentCountError');

        RawCommand::create();
    }

    /**
     * @group disconnected
     */
    public function testSetArguments(): void
    {
        $commandID = 'SET';
        $command = new RawCommand($commandID);

        $command->setArguments($commandArgs = ['foo', 'bar']);
        $this->assertSame($commandArgs, $command->getArguments());

        $command->setArguments($commandArgs = ['hoge', 'piyo']);
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments(): void
    {
        $commandID = 'SET';
        $command = new RawCommand($commandID);

        $command->setRawArguments($commandArgs = ['foo', 'bar']);
        $this->assertSame($commandArgs, $command->getArguments());

        $command->setRawArguments($commandArgs = ['hoge', 'piyo']);
        $this->assertSame($commandArgs, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex(): void
    {
        $command = new RawCommand('GET', ['key']);

        $this->assertSame('key', $command->getArgument(0));
        $this->assertNull($command->getArgument(1));
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetHash(): void
    {
        $slot = 1024;
        $arguments = ['key', 'value'];
        $command = new RawCommand('SET', $arguments);

        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $this->assertSame($slot, $command->getSlot());

        $command->setArguments(['hoge', 'piyo']);
        $this->assertNull($command->getSlot());
    }

    /**
     * @group disconnected
     */
    public function testNormalizesCommandIdentifiersToUppercase(): void
    {
        $command = new RawCommand('set', ['key', 'value']);

        $this->assertSame('SET', $command->getId());
    }
}
