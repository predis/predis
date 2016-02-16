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

use Predis\Command\CommandInterface;
use Predis\Command\Processor\ProcessorChain;
use PredisTestCase;

/**
 *
 */
abstract class PredisProfileTestCase extends PredisTestCase
{
    /**
     * Returns a new instance of the tested profile.
     *
     * @param string $version Version of Redis.
     *
     * @return ProfileInterface
     */
    protected function getProfile($version = null)
    {
        $this->markTestIncomplete('Server profile must be defined in '.get_class($this));
    }

    /**
     * Returns the expected version string for the tested profile.
     *
     * @return string Version string.
     */
    abstract protected function getExpectedVersion();

    /**
     * Returns the expected list of commands supported by the tested profile.
     *
     * @return array List of supported commands.
     */
    abstract protected function getExpectedCommands();

    /**
     * Returns the list of commands supported by the current
     * server profile.
     *
     * @param ProfileInterface $profile Server profile instance.
     *
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
        $profile = $this->getProfile();

        $this->assertEquals($this->getExpectedVersion(), $profile->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $profile = $this->getProfile();
        $expected = $this->getExpectedCommands();
        $commands = $this->getCommands($profile);

        $this->assertSame($expected, $commands);
    }

    /**
     * @group disconnected
     */
    public function testToString()
    {
        $this->assertEquals($this->getExpectedVersion(), $this->getProfile());
    }

    /**
     * @group disconnected
     */
    public function testSupportCommand()
    {
        $profile = $this->getProfile();

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
        $profile = $this->getProfile();

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
        $profile = $this->getProfile();

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
        $profile = $this->getProfile();
        $command = $this->getMock('Predis\Command\CommandInterface');

        $profile->defineCommand('mock', get_class($command));

        $this->assertTrue($profile->supportsCommand('mock'));
        $this->assertTrue($profile->supportsCommand('MOCK'));

        $this->assertSame(get_class($command), $profile->getCommandClass('mock'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class 'stdClass' is not a valid command class.
     */
    public function testDefineInvalidCommand()
    {
        $profile = $this->getProfile();

        $profile->defineCommand('mock', 'stdClass');
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments()
    {
        $profile = $this->getProfile();

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
        $profile = $this->getProfile();
        $arguments = array('foo', 'bar');

        $command = $profile->createCommand('set', $arguments);
        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Command 'UNKNOWN' is not a registered Redis command.
     */
    public function testCreateUndefinedCommand()
    {
        $profile = $this->getProfile();
        $profile->createCommand('unknown');
    }

    /**
     * @group disconnected
     */
    public function testGetDefaultProcessor()
    {
        $profile = $this->getProfile();

        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');

        $profile = $this->getProfile();
        $profile->setProcessor($processor);

        $this->assertSame($processor, $profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetAndUnsetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $profile = $this->getProfile();

        $profile->setProcessor($processor);
        $this->assertSame($processor, $profile->getProcessor());

        $profile->setProcessor(null);
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSingleProcessor()
    {
        // Could it be that objects passed to the return callback of a mocked
        // method are cloned instead of being passed by reference?
        $argsRef = null;

        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $processor->expects($this->once())
                  ->method('process')
                  ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
                  ->will($this->returnCallback(function (CommandInterface $cmd) use (&$argsRef) {
                        $cmd->setRawArguments($argsRef = array_map('strtoupper', $cmd->getArguments()));
                    }));

        $profile = $this->getProfile();
        $profile->setProcessor($processor);
        $profile->createCommand('set', array('foo', 'bar'));

        $this->assertSame(array('FOO', 'BAR'), $argsRef);
    }

    /**
     * @group disconnected
     */
    public function testChainOfProcessors()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $processor->expects($this->exactly(2))
                  ->method('process');

        $chain = new ProcessorChain();
        $chain->add($processor);
        $chain->add($processor);

        $profile = $this->getProfile();
        $profile->setProcessor($chain);
        $profile->createCommand('info');
    }
}
