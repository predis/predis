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

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
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

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $hashGenerator = $this->getMockBuilder('Predis\Cluster\Hash\HashGeneratorInterface')->getMock();

        $this->assertSame($hashGenerator, $option->filter($options, $hashGenerator));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsHashGeneratorInitializer()
    {
        $option = new CRC16();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $hashGenerator = $this->getMockBuilder('Predis\Cluster\Hash\HashGeneratorInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($hashGenerator));

        $this->assertSame($hashGenerator, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfHashGeneratorInitializer()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\CRC16 expects a valid hash generator');

        $option = new CRC16();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $wrongValue = $this->getMockBuilder('stdClass')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
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

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

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

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Cluster\Hash\PhpiredisCRC16', $option->filter($options, 'phpiredis'));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('String value for the crc16 option must be either `predis` or `phpiredis`');

        $option = new CRC16();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }
}
