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

/**
 *
 */
class ClientPrefixTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testValidationReturnsCommandProcessor()
    {
        $value = 'prefix:';
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientPrefix();

        $return = $option->filter($options, $value);

        $this->assertInstanceOf('Predis\Commands\Processors\ICommandProcessor', $return);
        $this->assertInstanceOf('Predis\Commands\Processors\KeyPrefixProcessor', $return);
        $this->assertEquals($value, $return->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testDefaultReturnsNull()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientPrefix();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testInvokeReturnsCommandProcessorOrNull()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientPrefix();

        $this->assertInstanceOf('Predis\Commands\Processors\ICommandProcessor', $option($options, 'prefix:'));
        $this->assertNull($option($options, null));
    }
}
