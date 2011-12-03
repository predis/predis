<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class CommandTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $this->assertInstanceOf('Predis\Commands\ICommand', $command);
    }

    /**
     * @group disconnected
     */
    public function testGetEmptyArguments()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments()
    {
        $arguments = array('1st', '2nd', '3rd');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->setRawArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     *
     * @todo Since Command::filterArguments is protected we cannot set an expectation
     *       for it when Command::setArguments() is invoked. I wonder how we can do that.
     */
    public function testSetArguments()
    {
        $arguments = array('1st', '2nd', '3rd');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex()
    {
        $arguments = array('1st', '2nd', '3rd');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
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
        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $this->assertEquals($response, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     * @protected
     */
    public function testCheckSameHashForKeys()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $checkSameHashForKeys = new \ReflectionMethod($command, 'checkSameHashForKeys');
        $checkSameHashForKeys->setAccessible(true);

        $this->assertTrue($checkSameHashForKeys->invoke($command, array('foo', '{foo}:bar')));
        $this->assertFalse($checkSameHashForKeys->invoke($command, array('foo', '{foo}:bar', 'foo:bar')));
    }

    /**
     * @group disconnected
     * @protected
     */
    public function testCanBeHashed()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $canBeHashed = new \ReflectionMethod($command, 'canBeHashed');
        $canBeHashed->setAccessible(true);

        $this->assertFalse($canBeHashed->invoke($command));

        $command->setRawArguments(array('key'));
        $this->assertTrue($canBeHashed->invoke($command));
    }

    /**
     * @group disconnected
     */
    public function testDoesNotReturnAnHashByDefault()
    {
        $distributor = $this->getMock('Predis\Distribution\INodeKeyGenerator');
        $distributor->expects($this->never())->method('generateKey');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');

        $command->getHash($distributor);
    }

    /**
     * @group disconnected
     */
    public function testReturnAnHashWhenCanBeHashedAndCachesIt()
    {
        $key = 'key';
        $hash = "$key-hash";

        $distributor = $this->getMock('Predis\Distribution\INodeKeyGenerator');
        $distributor->expects($this->once())
                    ->method('generateKey')
                    ->with($key)
                    ->will($this->returnValue($hash));

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->setRawArguments(array($key));

        $this->assertEquals($hash, $command->getHash($distributor));

        $this->assertEquals($hash, $command->getHash($distributor));
        $this->assertEquals($hash, $command->getHash($distributor));
    }

    /**
     * @group disconnected
     */
    public function testExtractsKeyTagsBeforeHashing()
    {
        $tag = 'key';
        $key = "{{$tag}}:ignore";
        $hash = "$tag-hash";

        $distributor = $this->getMock('Predis\Distribution\INodeKeyGenerator');
        $distributor->expects($this->once())
                    ->method('generateKey')
                    ->with($tag)
                    ->will($this->returnValue($hash));

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->setRawArguments(array($key));

        $this->assertEquals($hash, $command->getHash($distributor));
    }

    /**
     * @group disconnected
     */
    public function testToString()
    {
        $expected = 'SET key value';
        $arguments = array('key', 'value');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->expects($this->once())->method('getId')->will($this->returnValue('SET'));

        $command->setRawArguments($arguments);

        $this->assertEquals($expected, (string) $command);
    }

    /**
     * @group disconnected
     */
    public function testToStringWithLongArguments()
    {
        $expected = 'SET key abcdefghijklmnopqrstuvwxyz012345[...]';
        $arguments = array('key', 'abcdefghijklmnopqrstuvwxyz0123456789');

        $command = $this->getMockForAbstractClass('Predis\Commands\Command');
        $command->expects($this->once())->method('getId')->will($this->returnValue('SET'));

        $command->setRawArguments($arguments);

        $this->assertEquals($expected, (string) $command);
    }
}
