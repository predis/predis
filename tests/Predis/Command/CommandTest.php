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
class CommandTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
    }

    /**
     * @group disconnected
     */
    public function testGetEmptyArguments()
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments()
    {
        $arguments = array('1st', '2nd', '3rd');

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
    public function testSetArguments()
    {
        $arguments = array('1st', '2nd', '3rd');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex()
    {
        $arguments = array('1st', '2nd', '3rd');

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments[0], $command->getArgument(0));
        $this->assertEquals($arguments[2], $command->getArgument(2));
        $this->assertNull($command->getArgument(10));
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $response = 'response-buffer';
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEquals($response, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetSlot()
    {
        $slot = 1024;

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments(array('key'));

        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $this->assertSame($slot, $command->getSlot());

        $command->setArguments(array('key'));
        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $command->setRawArguments(array('key'));
        $this->assertNull($command->getSlot());
    }

    /**
     * @group disconnected
     */
    public function testNormalizeArguments()
    {
        $arguments = array('arg1', 'arg2', 'arg3', 'arg4');

        $this->assertSame($arguments, Command::normalizeArguments($arguments));
        $this->assertSame($arguments, Command::normalizeArguments(array($arguments)));

        $arguments = array(array(), array());
        $this->assertSame($arguments, Command::normalizeArguments($arguments));

        $arguments = array(new \stdClass());
        $this->assertSame($arguments, Command::normalizeArguments($arguments));
    }

    /**
     * @group disconnected
     */
    public function testNormalizeVariadic()
    {
        $arguments = array('key', 'value1', 'value2', 'value3');

        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
        $this->assertSame($arguments, Command::normalizeVariadic(array('key', array('value1', 'value2', 'value3'))));

        $arguments = array(new \stdClass());
        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
    }
}
