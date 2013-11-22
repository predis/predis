<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profile;

use PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Command\Processor\ProcessorChain;

/**
 *
 */
abstract class RedisProfileTestCase extends StandardTestCase
{
    /**
     * Returns a new instance of the tested profile.
     *
     * @return ProfileInterface
     */
    protected abstract function getProfileInstance();

    /**
     * Returns the expected version string for the tested profile.
     *
     * @return string Version string.
     */
    protected abstract function getExpectedVersion();

    /**
     * Returns the expected list of commands supported by the tested profile.
     *
     * @return array List of supported commands.
     */
    protected abstract function getExpectedCommands();

    /**
     * Returns the list of commands supported by the current
     * server profile.
     *
     * @param ProfileInterface $profile Server profile instance.
     * @return array
     */
    protected function getCommands(ProfileInterface $profile)
    {
        $commands = $profile->getSupportedCommands();

        return array_keys($commands);
    }

    /**
     * @group disconnected
     */
    public function testGetVersion()
    {
        $profile = $this->getProfileInstance();

        $this->assertEquals($this->getExpectedVersion(), $profile->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $profile = $this->getProfileInstance();
        $expected = $this->getExpectedCommands();
        $commands = $this->getCommands($profile);

        $this->assertSame($expected, $commands);
    }

    /**
     * @group disconnected
     */
    public function testToString()
    {
        $this->assertEquals($this->getExpectedVersion(), $this->getProfileInstance());
    }

    /**
     * @group disconnected
     */
    public function testSupportCommand()
    {
        $profile = $this->getProfileInstance();

        $this->assertTrue($profile->supportsCommand('info'));
        $this->assertTrue($profile->supportsCommand('INFO'));

        $this->assertFalse($profile->supportsCommand('unknown'));
        $this->assertFalse($profile->supportsCommand('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testSupportCommands()
    {
        $profile = $this->getProfileInstance();

        $this->assertTrue($profile->supportsCommands(array('get', 'set')));
        $this->assertTrue($profile->supportsCommands(array('GET', 'SET')));

        $this->assertFalse($profile->supportsCommands(array('get', 'unknown')));

        $this->assertFalse($profile->supportsCommands(array('unknown1', 'unknown2')));
    }

    /**
     * @group disconnected
     */
    public function testGetCommandClass()
    {
        $profile = $this->getProfileInstance();

        $this->assertSame('Predis\Command\ConnectionPing', $profile->getCommandClass('ping'));
        $this->assertSame('Predis\Command\ConnectionPing', $profile->getCommandClass('PING'));

        $this->assertNull($profile->getCommandClass('unknown'));
        $this->assertNull($profile->getCommandClass('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testDefineCommand()
    {
        $profile = $this->getProfileInstance();
        $command = $this->getMock('Predis\Command\CommandInterface');

        $profile->defineCommand('mock', get_class($command));

        $this->assertTrue($profile->supportsCommand('mock'));
        $this->assertTrue($profile->supportsCommand('MOCK'));

        $this->assertSame(get_class($command), $profile->getCommandClass('mock'));
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Cannot register 'stdClass' as it is not a valid Redis command
     */
    public function testDefineInvalidCommand()
    {
        $profile = $this->getProfileInstance();

        $profile->defineCommand('mock', 'stdClass');
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments()
    {
        $profile = $this->getProfileInstance();

        $command = $profile->createCommand('info');
        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('INFO', $command->getId());
        $this->assertEquals(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithArguments()
    {
        $profile = $this->getProfileInstance();
        $arguments = array('foo', 'bar');

        $command = $profile->createCommand('set', $arguments);
        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage 'unknown' is not a registered Redis command
     */
    public function testCreateUndefinedCommand()
    {
        $profile = $this->getProfileInstance();
        $profile->createCommand('unknown');
    }

    /**
     * @group disconnected
     */
    public function testGetDefaultProcessor()
    {
        $profile = $this->getProfileInstance();

        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\CommandProcessorInterface');

        $profile = $this->getProfileInstance();
        $profile->setProcessor($processor);

        $this->assertSame($processor, $profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetAndUnsetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\CommandProcessorInterface');
        $profile = $this->getProfileInstance();

        $profile->setProcessor($processor);
        $this->assertSame($processor, $profile->getProcessor());

        $profile->setProcessor(null);
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     * @todo Could it be that objects passed to the return callback of a mocked
     *       method are cloned instead of being passed by reference?
     */
    public function testSingleProcessor()
    {
        $argsRef = null;

        $processor = $this->getMock('Predis\Command\Processor\CommandProcessorInterface');
        $processor->expects($this->once())
                  ->method('process')
                  ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
                  ->will($this->returnCallback(function ($cmd) use (&$argsRef) {
                        $cmd->setRawArguments($argsRef = array_map('strtoupper', $cmd->getArguments()));
                    }));

        $profile = $this->getProfileInstance();
        $profile->setProcessor($processor);
        $command = $profile->createCommand('set', array('foo', 'bar'));

        $this->assertSame(array('FOO', 'BAR'), $argsRef);
    }

    /**
     * @group disconnected
     */
    public function testChainOfProcessors()
    {
        $processor = $this->getMock('Predis\Command\Processor\CommandProcessorInterface');
        $processor->expects($this->exactly(2))
                  ->method('process');

        $chain = new ProcessorChain();
        $chain->add($processor);
        $chain->add($processor);

        $profile = $this->getProfileInstance();
        $profile->setProcessor($chain);
        $profile->createCommand('info');
    }
}
