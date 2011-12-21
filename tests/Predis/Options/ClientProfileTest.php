<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Options;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Profiles\ServerProfile;
use Predis\Commands\Processors\KeyPrefixProcessor;

/**
 *
 */
class ClientProfileTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testValidationReturnsServerProfileWithStringValue()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $profile = $option->filter($options, '2.0');

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertEquals('2.0', $profile->getVersion());
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testValidationAcceptsProfileInstancesAsValue()
    {
        $value = ServerProfile::get('2.0');
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $profile = $option->filter($options, $value);

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertEquals('2.0', $profile->getVersion());
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testValidationAcceptsCallableObjectAsInitializers()
    {
        $value = $this->getMock('Predis\Profiles\IServerProfile');

        $initializer = $this->getMock('stdClass', array('__invoke'));
        $initializer->expects($this->once())
                    ->method('__invoke')
                    ->with($this->isInstanceOf('Predis\Options\IClientOptions'))
                    ->will($this->returnValue($value));

        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $profile = $option->filter($options, $initializer);

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertSame($value, $profile);
    }

    /**
     * @group disconnected
     */
    public function testValidationThrowsExceptionOnWrongInvalidArguments()
    {
        $this->setExpectedException('InvalidArgumentException');

        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $option->filter($options, new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testDefaultReturnsDefaultServerProfile()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $profile = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertInstanceOf(get_class(ServerProfile::getDefault()), $profile);
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testInvokeReturnsSpecifiedServerProfileOrDefault()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $profile = $option($options, '2.0');

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertEquals('2.0', $profile->getVersion());
        $this->assertNull($profile->getProcessor());

        $profile = $option($options, null);

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertInstanceOf(get_class(ServerProfile::getDefault()), $profile);
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     * @todo Can't we when trap __isset when mocking an interface? Doesn't seem to work here.
     */
    public function testFilterSetsPrefixProcessorFromClientOptions()
    {
        $options = $this->getMock('Predis\Options\ClientOptions', array('__isset', '__get'));
        $options->expects($this->once())
                ->method('__isset')
                ->with('prefix')
                ->will($this->returnValue(true));
        $options->expects($this->once())
                ->method('__get')
                ->with('prefix')
                ->will($this->returnValue(new KeyPrefixProcessor('prefix:')));

        $option = new ClientProfile();

        $profile = $option->filter($options, '2.0');

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertEquals('2.0', $profile->getVersion());
        $this->assertInstanceOf('Predis\Commands\Processors\KeyPrefixProcessor', $profile->getProcessor());
        $this->assertEquals('prefix:', $profile->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     * @todo Can't we when trap __isset when mocking an interface? Doesn't seem to work here.
     */
    public function testDefaultSetsPrefixProcessorFromClientOptions()
    {
        $options = $this->getMock('Predis\Options\ClientOptions', array('__isset', '__get'));
        $options->expects($this->once())
                ->method('__isset')
                ->with('prefix')
                ->will($this->returnValue(true));
        $options->expects($this->once())
                ->method('__get')
                ->with('prefix')
                ->will($this->returnValue(new KeyPrefixProcessor('prefix:')));

        $option = new ClientProfile();

        $profile = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertInstanceOf(get_class(ServerProfile::getDefault()), $profile);
        $this->assertInstanceOf('Predis\Commands\Processors\KeyPrefixProcessor', $profile->getProcessor());
        $this->assertEquals('prefix:', $profile->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testValidationDoesNotSetPrefixProcessorWhenValueIsProfileInstance()
    {
        $options = $this->getMock('Predis\Options\ClientOptions', array('__isset', '__get'));
        $options->expects($this->never())->method('__isset');
        $options->expects($this->never())->method('__get');

        $option = new ClientProfile();

        $profile = $option->filter($options, ServerProfile::getDefault());

        $this->assertInstanceOf('Predis\Profiles\IServerProfile', $profile);
        $this->assertNull($profile->getProcessor());
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid value for the profile option
     */
    public function testValidationThrowsExceptionOnInvalidObjectReturnedByCallback()
    {
        $value = function($options) { return new \stdClass(); };

        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientProfile();

        $option->filter($options, $value);
    }
}
