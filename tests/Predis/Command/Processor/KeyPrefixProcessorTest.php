<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
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
}
