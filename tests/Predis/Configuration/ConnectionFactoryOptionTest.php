<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use InvalidArgumentException;
use stdClass;
use PredisTestCase;
use Predis\Connection\ConnectionFactory;

/**
 *
 */
class ConnectionFactoryOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\ConnectionFactory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $class = get_class($this->getMock('Predis\Connection\SingleConnectionInterface'));
        $value = array('tcp' => $class, 'redis' => $class);

        $default = $this->getMock('Predis\Connection\ConnectionFactoryInterface');
        $default->expects($this->exactly(2))
                ->method('define')
                ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        $option = $this->getMock('Predis\Configuration\ConnectionFactoryOption', array('getDefault'));
        $option->expects($this->once())
               ->method('getDefault')
               ->with($options)
               ->will($this->returnValue($default));

        $this->assertInstanceOf('Predis\Connection\ConnectionFactoryInterface', $factory = $option->filter($options, $value));
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsConnectionFactoryInstance()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $value = $this->getMock('Predis\Connection\ConnectionFactoryInterface');

        $option = $this->getMock('Predis\Configuration\ConnectionFactoryOption', array('getDefault'));
        $option->expects($this->never())->method('getDefault');

        $this->assertSame($value, $option->filter($options, $value));
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidArguments()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, new stdClass);
    }
}
