<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Cluster\Hash;
use PredisTestCase;

/**
 *
 */
class CRC16Test extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $hashGenerator = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Cluster\Hash\HashGeneratorInterface', $hashGenerator);

        if (function_exists('phpiredis_utils_crc16')) {
            $this->assertInstanceOf('Predis\Cluster\Hash\PhpiredisCRC16', $hashGenerator);
        } else {
            $this->assertInstanceOf('Predis\Cluster\Hash\CRC16', $hashGenerator);
        }
    }

    /**
     * @group disconnected
     */
    public function testAcceptsHashGeneratorInstance()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $hashGenerator = $this->getMock('Predis\Cluster\Hash\HashGeneratorInterface');

        $this->assertSame($hashGenerator, $option->filter($options, $hashGenerator));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsHashGeneratorInitializer()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $hashGenerator = $this->getMock('Predis\Cluster\Hash\HashGeneratorInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($hashGenerator));

        $this->assertSame($hashGenerator, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\CRC16 expects a valid hash generator
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfHashGeneratorInitializer()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $wrongValue = $this->getMock('stdClass');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($wrongValue));

        $option->filter($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringPredis()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Cluster\Hash\CRC16', $option->filter($options, 'predis'));
    }

    /**
     * @group disconnected
     * @group ext-phpiredis
     * @requires extension phpiredis
     * @requires function phpiredis_utils_crc16
     */
    public function testAcceptsShortNameStringPhpiredis()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Cluster\Hash\PhpiredisCRC16', $option->filter($options, 'phpiredis'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage String value for the crc16 option must be either `predis` or `phpiredis`
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $option = new CRC16();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, 'unknown');
    }
}
