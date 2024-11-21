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
use stdClass;

class CommandTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface(): void
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
    }

    /**
     * @group disconnected
     */
    public function testGetEmptyArguments(): void
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     *
     * @todo We cannot set an expectation for Command::filterArguments() when we
     *       invoke Command::setArguments() because it is protected.
     */
    public function testSetArguments(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments[0], $command->getArgument(0));
        $this->assertEquals($arguments[2], $command->getArgument(2));
        $this->assertNull($command->getArgument(10));
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $response = 'response-buffer';
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEquals($response, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetSlot(): void
    {
        $slot = 1024;

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments(['key']);

        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $this->assertSame($slot, $command->getSlot());

        $command->setArguments(['key']);
        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $command->setRawArguments(['key']);
        $this->assertNull($command->getSlot());
    }

    /**
     * @group disconnected
     */
    public function testNormalizeArguments(): void
    {
        $arguments = ['arg1', 'arg2', 'arg3', 'arg4'];

        $this->assertSame($arguments, Command::normalizeArguments($arguments));
        $this->assertSame($arguments, Command::normalizeArguments([$arguments]));

        $arguments = [[], []];
        $this->assertSame($arguments, Command::normalizeArguments($arguments));

        $arguments = [new stdClass()];
        $this->assertSame($arguments, Command::normalizeArguments($arguments));
    }

    /**
     * @group disconnected
     */
    public function testNormalizeVariadic(): void
    {
        $arguments = ['key', 'value1', 'value2', 'value3'];

        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
        $this->assertSame($arguments, Command::normalizeVariadic(['key', ['value1', 'value2', 'value3']]));

        $arguments = [new stdClass()];
        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
    }
}
