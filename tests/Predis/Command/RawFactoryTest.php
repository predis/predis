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

class RawFactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportForAnyCommand(): void
    {
        $factory = new RawFactory();

        $this->assertTrue($factory->supports('info'));
        $this->assertTrue($factory->supports('INFO'));

        $this->assertTrue($factory->supports('unknown'));
        $this->assertTrue($factory->supports('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testSupportForAnyCommands(): void
    {
        $factory = new RawFactory();

        $this->assertTrue($factory->supports('get', 'set'));
        $this->assertTrue($factory->supports('GET', 'SET'));

        $this->assertTrue($factory->supports('get', 'unknown'));

        $this->assertTrue($factory->supports('unknown1', 'unknown2'));
    }

    /**
     * @group disconnected
     */
    public function testCreateInstanceOfRawCommand(): void
    {
        $factory = new RawFactory();

        $command = $factory->create('info');
        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertInstanceOf('Predis\Command\RawCommand', $command);

        $command = $factory->create('unknown');
        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertInstanceOf('Predis\Command\RawCommand', $command);
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments(): void
    {
        $factory = new RawFactory();

        $command = $factory->create('info');

        $this->assertInstanceOf('Predis\Command\RawCommand', $command);
        $this->assertEquals('INFO', $command->getId());
        $this->assertEquals([], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithArguments(): void
    {
        $factory = new RawFactory();

        $arguments = ['foo', 'bar'];
        $command = $factory->create('set', $arguments);

        $this->assertInstanceOf('Predis\Command\RawCommand', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }
}
